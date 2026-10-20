<?php

namespace App\Services\Auth;

use App\Exception\Auth\InvalidPasswordResetTokenException;

final class PasswordResetTokenService extends AbstractUserTokenService
{
    protected function getType(): string
    {
        return 'password_reset';
    }

    protected function getTtl(): string
    {
        return '+1 hour';
    }

    protected function getInvalidTokenException(): \Throwable
    {
        return new InvalidPasswordResetTokenException();
    }
}