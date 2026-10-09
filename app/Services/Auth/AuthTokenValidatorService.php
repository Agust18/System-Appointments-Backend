<?php

namespace App\Services\Auth;

use App\Entity\User\User;
use App\Models\User\UserModel;


final class AuthTokenValidatorService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function validate(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        // findByToken ya descarta los tokens vencidos (token_expiration_date >= ahora).
        return $this->userModel->findByToken($token);
    }
}
