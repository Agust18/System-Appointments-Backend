<?php

namespace App\Services\auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\UserEmailTemplateRenderer;
use App\Entity\User\User;

final class SendVerificationEmailService
{
    private EmailVerificationTokenService $tokenService;
    private EmailSenderService $emailSender;
    private UserEmailTemplateRenderer $templateRenderer;

    public function __construct()
    {
        $this->tokenService = new EmailVerificationTokenService();
        $this->emailSender = new EmailSenderService();
        $this->templateRenderer = new UserEmailTemplateRenderer();
    }

    public function send(User $user): void
    {
        $token = $this->tokenService->generateForUser($user->getId());
        $url = rtrim(env('app.frontendUrl', 'http://localhost:5173'), '/')
            . '/verify-email?token=' . urlencode($token->getToken());

        $this->emailSender->send(new EmailRequest(
            to: $user->getEmail(),
            subject: 'Verificá tu cuenta',
            body: $this->templateRenderer->renderVerificationBody($user, $url),
        ));
    }
}