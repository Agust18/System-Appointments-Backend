<?php

namespace App\Services\auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\UserEmailTemplateRendererPassword;
use App\Entity\User\User;

final class SendPasswordResetEmailService
{
    private PasswordResetTokenService $tokenService;
    private EmailSenderService $emailSender;
    private UserEmailTemplateRendererPassword $templateRenderer;

    public function __construct()
    {
        $this->tokenService = new PasswordResetTokenService();
        $this->emailSender = new EmailSenderService();
        $this->templateRenderer = new UserEmailTemplateRendererPassword();
    }

    public function send(User $user): void
    {
        $token = $this->tokenService->generateForUser($user->getId());
        $url = rtrim(env('app.frontendUrl', 'http://localhost:5173'), '/')
            . '/reset-password?token=' . urlencode($token->getToken());

        // Sin contraseña no hay nada que "restablecer": el mismo link sirve para crear la cuenta.
        if ($user->hasAccount()) {
            $subject = 'Restablecer contraseña';
            $body = $this->templateRenderer->renderPasswordResetBody($user, $url);
        } else {
            $subject = 'Activá tu cuenta';
            $body = $this->templateRenderer->renderAccountActivationBody($user, $url);
        }

        $this->emailSender->send(new EmailRequest(
            to: $user->getEmail(),
            subject: $subject,
            body: $body,
        ));
    }
}