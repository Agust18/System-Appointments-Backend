<?php

namespace App\Dto\Request\Platform;

final readonly class PlatformLoginRequest
{
    public function __construct(
        private string $email,
        private string $password,
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public static function rules(): array
    {
        return [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];
    }

    public static function messages(): array
    {
        return [
            'email' => [
                'required'    => 'Ingresá tu email.',
                'valid_email' => 'Ese email no es válido.',
            ],
            'password' => [
                'required' => 'Ingresá tu contraseña.',
            ],
        ];
    }
}
