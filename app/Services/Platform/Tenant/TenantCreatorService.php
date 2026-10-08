<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantToTenantResponseConverter;
use App\Dto\Request\Platform\TenantRequest;
use App\Dto\Response\Platform\TenantResponse;
use App\Entity\Tenant\Tenant;
use App\Entity\Tenant\TenantSettings;
use App\Models\Tenant\TenantModel;
use App\Models\Tenant\TenantSettingsModel;
use App\Services\Platform\Tenant\Validations\TenantCreateValidatorService;
use App\Services\User\UserCreatorService;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;
use Throwable;

/**
 * Alta de un Tenant con su primer ADMIN, en una sola transacción (UserCreatorService no abre
 * ninguna, así que no hay transacciones anidadas). Junto con el Tenant se crea su fila de
 * `tenant_settings`. El ADMIN nace verificado y con la contraseña temporal del body; no se envía
 * ningún email (llega con el Paso 4).
 */
final class TenantCreatorService
{
    private BaseConnection $db;
    private TenantModel $tenantModel;
    private TenantSettingsModel $tenantSettingsModel;
    private TenantCreateValidatorService $validator;
    private UserCreatorService $userCreatorService;
    private TenantToTenantResponseConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->tenantModel = new TenantModel();
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->validator = new TenantCreateValidatorService();
        $this->userCreatorService = new UserCreatorService();
        $this->converter = new TenantToTenantResponseConverter();
    }

    public function create(TenantRequest $request): TenantResponse
    {
        $this->validator->validate($request);

        $previousTenant = TenantContext::current();

        $this->db->transBegin();

        try {
            $tenant = $this->tenantModel->insert(Tenant::fromRequest($request));
            $this->assertTransactionIsHealthy($tenant);

            // UserModel y los validators leen TenantContext: el ADMIN se crea dentro del Tenant nuevo.
            TenantContext::set($tenant);

            $settings = $this->tenantSettingsModel->insert(TenantSettings::defaultsFor($tenant));
            $this->assertTransactionIsHealthy($settings);

            $admin = $this->userCreatorService->create($request->toAdminUserRequest(), true);
            $this->assertTransactionIsHealthy($admin);

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        } finally {
            $this->restoreContext($previousTenant);
        }

        return $this->converter->convert($tenant);
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
