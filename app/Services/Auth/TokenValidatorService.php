<?php

namespace App\Services\Auth;

use App\Entity\User\User;
use App\Models\User\UserModel;
use DateTime;

final class TokenValidatorService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function validate(string $token): ?User
    {
        if (empty($token)) {
            error_log("Token vacío");
            return null;
        }

        $user = $this->userModel->findByToken($token);

        if (!$user) {
            error_log("TokenValidatorService - Usuario NO encontrado");
            return null;
        }

        error_log("TokenValidatorService - Usuario encontrado: " . $user->getEmail());

    
        $tokenExpiration = $user->getTokenExpirationDate();
        $now = new DateTime();

        

        if ($tokenExpiration && $now > $tokenExpiration) {
            error_log("TokenValidatorService - Token EXPIRADO");
            return null;
        }

        error_log("TokenValidatorService - Token válido");
        return $user;
    }
}