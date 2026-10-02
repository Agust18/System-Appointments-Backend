<?php

namespace App\Services\auth;

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

        // El token llegó al email guardado del usuario: fijar la contraseña con él
        // prueba el control de ese email. Sin esto, un usuario sin cuenta que activa
        // la suya quedaría sin poder iniciar sesión (login exige is_verified).
        if (!$user->getIsVerified()) {
            $this->userModel->markAsVerified($user->getId());
        }

        $this->tokenService->markAsUsed($token);
    }


    
}