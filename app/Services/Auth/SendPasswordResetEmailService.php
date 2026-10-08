<?php

namespace App\Services\auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\UserEmailTemplateRendererPassword;
use App\Entity\User\User;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class SendPasswordResetEmailService
{
    private PasswordResetTokenService $tokenService;
    /** @var callable(EmailRequest): mixed */
    private $sender;
    private UserEmailTemplateRendererPassword $templateRenderer;
    private TenantEmailSettingsResolverService $settingsResolver;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->tokenService = new PasswordResetTokenService();
        $this->sender = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
        $this->templateRenderer = new UserEmailTemplateRendererPassword();
    }

    public function send(User $user): void
    {
        // Antes del token: sin frontend_url no se genera uno que quede huérfano.
        $frontendUrl = $this->settingsResolver->frontendUrl();

        $token = $this->tokenService->generateForUser($user->getId());
        $url = $frontendUrl . '/reset-password?token=' . urlencode($token->getToken());

        // Sin contraseña no hay nada que "restablecer": el mismo link sirve para crear la cuenta.
        if ($user->hasAccount()) {
            $subject = 'Restablecer contraseña';
            $body = $this->templateRenderer->renderPasswordResetBody($user, $url);
        } else {
            $subject = 'Activá tu cuenta';
            $body = $this->templateRenderer->renderAccountActivationBody($user, $url);
        }

        ($this->sender)(new EmailRequest(
            to: $user->getEmail(),
            subject: $subject,
            body: $body,
            replyTo: $this->settingsResolver->replyTo(),
        ));
    }
}