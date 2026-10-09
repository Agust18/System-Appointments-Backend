<?php

namespace App\Dto\Request\Platform;


final readonly class PlatformAdminRequest
{
    public function __construct(
        private string $email,
        private string $firstName,
        private string $lastName,
        private string $password,
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public static function rules(): array
    {
        return [
            'email'     => 'required|valid_email|max_length[255]',
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'password'  => 'required|min_length[12]|max_length[72]',
        ];
    }

    public static function messages(): array
    {
        return [
            'email' => [
                'required'    => 'Ingresá un email.',
                'valid_email' => 'Ese email no es válido.',
                'max_length'  => 'El email es muy largo.',
            ],
            'firstName' => [
                'required'   => 'Ingresá el nombre.',
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'lastName' => [
                'required'   => 'Ingresá el apellido.',
                'min_length' => 'El apellido es muy corto.',
                'max_length' => 'El apellido es muy largo.',
            ],
            'password' => [
                'required'   => 'Ingresá una contraseña.',
                'min_length' => 'La contraseña de un administrador de plataforma necesita al menos 12 caracteres.',
                'max_length' => 'La contraseña no puede superar los 72 caracteres (límite de bcrypt).',
            ],
        ];
    }
}
