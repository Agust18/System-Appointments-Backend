<?php

namespace App\Services\auth;

use App\Dto\Request\Email\EmailRequest;
use App\Email\EmailSenderService;
use App\Email\User\UserEmailTemplateRenderer;
use App\Entity\User\User;
use App\Services\Tenant\TenantEmailSettingsResolverService;

final class SendVerificationEmailService
{
    private EmailVerificationTokenService $tokenService;
    /** @var callable(EmailRequest): mixed */
    private $sender;
    private UserEmailTemplateRenderer $templateRenderer;
    private TenantEmailSettingsResolverService $settingsResolver;

    /**
     * @param callable(EmailRequest): mixed|null $sender Punto de observación para tests;
     *        por defecto envía con EmailSenderService.
     */
    public function __construct(?callable $sender = null)
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->tokenService = new EmailVerificationTokenService();
        $this->sender = $sender ?? static fn (EmailRequest $request) => (new EmailSenderService())->send($request);
        $this->templateRenderer = new UserEmailTemplateRenderer();
    }

    public function send(User $user): void
    {
        // Antes del token: sin frontend_url no se genera uno que quede huérfano.
        $frontendUrl = $this->settingsResolver->frontendUrl();

        $token = $this->tokenService->generateForUser($user->getId());
        $url = $frontendUrl . '/verify-email?token=' . urlencode($token->getToken());

        ($this->sender)(new EmailRequest(
            to: $user->getEmail(),
            subject: 'Verificá tu cuenta',
            body: $this->templateRenderer->renderVerificationBody($user, $url),
            replyTo: $this->settingsResolver->replyTo(),
        ));
    }
}