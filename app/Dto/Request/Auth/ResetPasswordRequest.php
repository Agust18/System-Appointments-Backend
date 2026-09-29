<?php

namespace App\Dto\Request\Auth;

final class ResetPasswordRequest
{
    public function __construct(
        private string $token,
        private string $password
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public static function rules(): array
    {
        return [
            'token'    => 'required|string',
            'password' => 'required|min_length[8]',
        ];
    }

    public static function messages(): array
    {
        return [
            'token' => [
                'required' => 'Falta el token de recuperación.',
            ],
            'password' => [
                'required'   => 'Ingresá tu nueva contraseña.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
        ];
    }
}