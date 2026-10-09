<?php

namespace App\Dto\Request\Auth;

final class ForgotPasswordRequest
{
    public function __construct(
        private string $email
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public static function rules(): array
    {
        return [
            'email' => 'required|valid_email',
        ];
    }

    public static function messages(): array
    {
        return [
            'email' => [
                'required'    => 'Ingresá tu email.',
                'valid_email' => 'Ese email no es válido.',
            ],
        ];
    }
}