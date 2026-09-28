<?php

namespace App\Exception\Auth;

use RuntimeException;

final class InvalidPasswordResetTokenException extends RuntimeException
{
    public function __construct(string $message = 'El token de restablecimiento de contraseña es inválido o ha expirado.')
    {
        parent::__construct($message, 400);
    }
}