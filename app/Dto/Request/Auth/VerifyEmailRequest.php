<?php

namespace App\Dto\Request\Auth;

final class VerifyEmailRequest
{
    public function __construct(private string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public static function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }

    public static function messages(): array
    {
        return [
            'token' => [
                'required' => 'Falta el token de verificación.',
            ],
        ];
    }
}