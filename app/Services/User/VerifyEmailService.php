<?php

namespace App\Services\User;

use App\Dto\Request\Auth\VerifyEmailRequest;
use App\Exception\User\UserAlreadyVerifiedException;
use App\Exception\User\InvalidVerificationTokenException;
use App\Models\User\UserModel;

final class VerifyEmailService
{
    private EmailVerificationTokenService $tokenService;
    private UserModel $userModel;

    public function __construct()
    {
        $this->tokenService = new EmailVerificationTokenService();
        $this->userModel = new UserModel();
    }

    public function verify(VerifyEmailRequest $request): void
    {
        $token = $this->tokenService->validate($request->getToken());
        $user = $this->userModel->find($token->getUserId());

        if (!$user) {
            throw new InvalidVerificationTokenException();
        }

        if ($user->getIsVerified()) {
            throw new UserAlreadyVerifiedException();
        }

        $this->userModel->markAsVerified($user->getId());
        $this->tokenService->markAsUsed($token);
    }
}