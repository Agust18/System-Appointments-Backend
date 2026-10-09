<?php

namespace App\Services\Auth;

use App\Dto\Request\Auth\ResetPasswordRequest;
use App\Exception\Auth\InvalidPasswordResetTokenException;
use App\Models\User\UserModel;

final class ResetPasswordService
{
    private PasswordResetTokenService $tokenService;
    private UserModel $userModel;

    public function __construct()
    {
        $this->tokenService = new PasswordResetTokenService();
        $this->userModel = new UserModel();
    }

    public function reset(ResetPasswordRequest $request): void
    {
        $token = $this->tokenService->validate($request->getToken());
        $user = $this->userModel->find($token->getUserId());

        if (!$user) {
            throw new InvalidPasswordResetTokenException();
        }

        $hashedPassword = password_hash($request->getPassword(), PASSWORD_BCRYPT);

        $this->userModel->updatePassword($user->getId(), $hashedPassword);

       
        if (!$user->getIsVerified()) {
            $this->userModel->markAsVerified($user->getId());
        }

        $this->tokenService->markAsUsed($token);
    }
}