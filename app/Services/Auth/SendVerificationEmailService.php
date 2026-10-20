<?php

namespace App\Services\Auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\EmailVerificationEmailTemplate;
use App\Entity\User\User;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class SendVerificationEmailService
{
    private EmailVerificationTokenService $tokenService;
    private EmailVerificationEmailTemplate $template;
    private TenantEmailSettingsResolverService $settingsResolver;
    private EmailSenderService $emailSender;

    public function __construct()
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->tokenService     = new EmailVerificationTokenService();
        $this->template         = new EmailVerificationEmailTemplate();
        $this->emailSender      = new EmailSenderService();
    }

    public function send(User $user): void
    {
      
        $frontendUrl = $this->settingsResolver->frontendUrl();

        $token = $this->tokenService->generateForUser($user->getId());
        $url = $frontendUrl . '/verify-email?token=' . urlencode($token->getToken());

        $request = new EmailRequest(
            to: $user->getEmail(),
            subject: 'Verificá tu cuenta',
            body: $this->template->render($user, $url),
            replyTo: $this->settingsResolver->replyTo(),
        );

        $this->emailSender->send($request);
    }
}
