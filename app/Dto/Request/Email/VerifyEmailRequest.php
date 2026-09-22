<?php

namespace App\Dto\Request\Email;

final class VerifyEmailRequest
{
    public function __construct(private string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}