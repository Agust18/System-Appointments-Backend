<?php

namespace App\Services\Platform\Tenant;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\Platform\TenantAdminWelcomeEmailTemplateRenderer;
use App\Models\User\UserModel;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use Throwable;

/**
 * Email de bienvenida al primer ADMIN de un Tenant nuevo, con un link para elegir su contraseña
 * (nunca lleva una contraseña). Corre con el TenantContext del Tenant nuevo, DESPUÉS del commit
 * del alta, y nunca lanza: el Tenant ya existe y un fallo de SMTP no debe deshacerlo.
 */
final class SendTenantAdminWelcomeEmailService
{
    private UserModel $userModel;
    private TenantAdminWelcomeTokenService $tokenService;
    private TenantAdminWelcomeEmailTemplateRenderer $renderer;
    /** @var callable(EmailRequest): mixed */
    private $sender;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->userModel    = new UserModel();
        $this->tokenService = new TenantAdminWelcomeTokenService();
        $this->renderer     = new TenantAdminWelcomeEmailTemplateRenderer();
        $this->sender       = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
    }

    /**
     * @return bool true si el email salió; false si falló algo (queda en el log).
     */
    public function send(int $adminUserId, string $tenantName): bool
    {
        try {
            $admin = $this->userModel->find($adminUserId);
            if ($admin === null) {
                throw new \RuntimeException("No se encontró el ADMIN {$adminUserId} del Tenant nuevo.");
            }

            $settings    = new TenantEmailSettingsResolverService();
            $frontendUrl = $settings->frontendUrl();

            $token = $this->tokenService->generateForUser($admin->getId());
            $url   = $frontendUrl . '/reset-password?token=' . urlencode($token->getToken());

            ($this->sender)(new EmailRequest(
                to:      $admin->getEmail(),
                subject: 'Tu cuenta de administrador está lista',
                body:    $this->renderer->renderWelcomeBody($admin->getFirstName(), $tenantName, $url, TenantAdminWelcomeTokenService::TTL_HOURS),
                replyTo: $settings->replyTo(),
            ));

            return true;
        } catch (Throwable $e) {
            log_message('error', "No se pudo enviar el email de bienvenida al ADMIN {$adminUserId}: {$e->getMessage()}");

            return false;
        }
    }
}
