<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantToTenantCreatedResponseConverter;
use App\Dto\Request\Platform\TenantRequest;
use App\Dto\Response\Platform\TenantCreatedResponse;
use App\Entity\Tenant\Tenant;
use App\Entity\Tenant\TenantSettings;
use App\Models\Tenant\TenantModel;
use App\Models\Tenant\TenantSettingsModel;
use App\Services\Platform\Tenant\Validations\TenantCreateValidatorService;
use App\Services\User\UserCreatorService;
use App\Tenancy\FrontendUrl;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;
use Throwable;


final class TenantCreatorService
{
    private BaseConnection $db;
    private TenantModel $tenantModel;
    private TenantSettingsModel $tenantSettingsModel;
    private TenantCreateValidatorService $validator;
    private UserCreatorService $userCreatorService;
    private SendTenantAdminWelcomeEmailService $welcomeEmailService;
    private TenantToTenantCreatedResponseConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->tenantModel = new TenantModel();
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->validator = new TenantCreateValidatorService();
        $this->userCreatorService = new UserCreatorService();
        $this->welcomeEmailService = new SendTenantAdminWelcomeEmailService();
        $this->converter = new TenantToTenantCreatedResponseConverter();
    }

    public function create(TenantRequest $request): TenantCreatedResponse
    {
        $this->validator->validate($request);

        $previousTenant = TenantContext::current();
        $adminId = 0;

        $this->db->transBegin();

        try {
            $tenant = $this->tenantModel->insert(Tenant::fromRequest($request));
            $this->assertTransactionIsHealthy($tenant);

            // UserModel y los validators leen TenantContext: el ADMIN se crea dentro del Tenant nuevo.
            TenantContext::set($tenant);

            $settings = $this->tenantSettingsModel->insert(TenantSettings::defaultsFor($tenant, FrontendUrl::normalize($request->getFrontendUrl(), true)));
            $this->assertTransactionIsHealthy($settings);

            $admin = $this->userCreatorService->create($request->toAdminUserRequest(), true);
            $this->assertTransactionIsHealthy($admin);

            $this->db->transCommit();
            $adminId = $admin->id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        } finally {
            $this->restoreContext($previousTenant);
        }

        $welcomeEmailSent = $this->sendWelcomeEmail($tenant, $adminId, $previousTenant);

        return $this->converter->convert($tenant, $welcomeEmailSent);
    }

    /**
     * Fuera de la transacción (un SMTP lento no la deja abierta) y con el Tenant nuevo en el
     * contexto, porque el token y el resolver de settings leen TenantContext. El Service no lanza.
     */
    private function sendWelcomeEmail(Tenant $tenant, int $adminId, ?Tenant $previousTenant): bool
    {
        TenantContext::set($tenant);

        try {
            return $this->welcomeEmailService->send($adminId, $tenant->getName());
        } finally {
            $this->restoreContext($previousTenant);
        }
    }

    /**
     * Dentro de una transacción CI4 no siempre lanza ante un INSERT fallido: se verifica a mano.
     */
    private function assertTransactionIsHealthy(?object $inserted): void
    {
        if ($inserted === null || $this->db->transStatus() === false) {
            throw new RuntimeException('No se pudo crear el cliente.', 500);
        }
    }

    private function restoreContext(?Tenant $previousTenant): void
    {
        if ($previousTenant === null) {
            TenantContext::clear();

            return;
        }

        TenantContext::set($previousTenant);
    }
}
