<?php

namespace App\Services\Auth;

use App\Exception\Auth\InvalidVerificationTokenException;

final class EmailVerificationTokenService extends AbstractUserTokenService
{
    protected function getType(): string
    {
        return 'email_verification';
    }

    protected function getTtl(): string
    {
        return '+24 hours';
    }

    protected function getInvalidTokenException(): \Throwable
    {
        return new InvalidVerificationTokenException();
    }
}
