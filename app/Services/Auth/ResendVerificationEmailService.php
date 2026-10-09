<?php

namespace App\Services\auth;

use App\Dto\Request\Auth\ResendVerificationEmailRequest;
use App\Exception\Auth\AlreadyVerifiedException;
use App\Services\User\UserFinderByEmailService;

final class ResendVerificationEmailService
{
    private SendVerificationEmailService $verificationEmailService;
    private UserFinderByEmailService $userFinderByEmailService;

    public function __construct()
    {
        $this->userFinderByEmailService = new UserFinderByEmailService();
        $this->verificationEmailService = new SendVerificationEmailService();
    }

    public function resend(ResendVerificationEmailRequest $request): void
    {
        $user = $this->userFinderByEmailService->find($request->getEmail());

        if ($user->getIsVerified()) {
            throw new AlreadyVerifiedException();
        }

        $this->verificationEmailService->send($user);
    }
}
