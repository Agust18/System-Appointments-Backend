<?php

namespace App\Services\Auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\PasswordResetEmailTemplate;
use App\Entity\User\User;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class SendPasswordResetEmailService
{
    private PasswordResetTokenService $tokenService;
    private PasswordResetEmailTemplate $resetTemplate;
    private TenantEmailSettingsResolverService $settingsResolver;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->settingsResolver   = new TenantEmailSettingsResolverService();
        $this->tokenService       = new PasswordResetTokenService();
        $this->resetTemplate      = new PasswordResetEmailTemplate();
        $this->emailSender        = new EmailSenderService();
    }

    public function send(User $user): void
    {
      
        $frontendUrl = $this->settingsResolver->frontendUrl();

        $token = $this->tokenService->generateForUser($user->getId());
        $url = $frontendUrl . '/reset-password?token=' . urlencode($token->getToken());

        // El mismo mail y link sirven para quien olvidó su contraseña y para el ADMIN que aún no eligió la suya.
        $request = new EmailRequest(
            to: $user->getEmail(),
            subject: 'Restablecer contraseña',
            body: $this->resetTemplate->render($user, $url),
            replyTo: $this->settingsResolver->replyTo(),
        );

        $this->emailSender->send($request);
    }
}
