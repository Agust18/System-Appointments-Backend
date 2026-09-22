<?php

namespace App\Dto\Request\Auth;

final class ResendVerificationEmailRequest
{
    public function __construct(private string $email)
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}