<?php

namespace App\Services\Auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\AccountActivationEmailTemplate;
use App\Email\User\PasswordResetEmailTemplate;
use App\Entity\User\User;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class SendPasswordResetEmailService
{
    private PasswordResetTokenService $tokenService;
    private PasswordResetEmailTemplate $resetTemplate;
    private AccountActivationEmailTemplate $activationTemplate;
    private TenantEmailSettingsResolverService $settingsResolver;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->settingsResolver   = new TenantEmailSettingsResolverService();
        $this->tokenService       = new PasswordResetTokenService();
        $this->resetTemplate      = new PasswordResetEmailTemplate();
        $this->activationTemplate = new AccountActivationEmailTemplate();
        $this->emailSender        = new EmailSenderService();
    }

    public function send(User $user): void
    {
      
        $frontendUrl = $this->settingsResolver->frontendUrl();

        $token = $this->tokenService->generateForUser($user->getId());
        $url = $frontendUrl . '/reset-password?token=' . urlencode($token->getToken());

        // Sin contraseña no hay nada que "restablecer": el mismo link sirve para crear la cuenta.
        if ($user->hasAccount()) {
            $subject = 'Restablecer contraseña';
            $body = $this->resetTemplate->render($user, $url);
        } else {
            $subject = 'Activá tu cuenta';
            $body = $this->activationTemplate->render($user, $url);
        }

        $request = new EmailRequest(
            to: $user->getEmail(),
            subject: $subject,
            body: $body,
            replyTo: $this->settingsResolver->replyTo(),
        );

        $this->emailSender->send($request);
    }
}
