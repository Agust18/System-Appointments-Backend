<?php
namespace App\Dto\Request\User;
final readonly class UserRequest{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private string $email,
        private string $password,
        private int $dni,
        private bool $isActive,
        private ?int $roleId

    ){}

    public function getFirstName():string
    {
        return $this->firstName;
    }

    public function getLastName():string
    {
        return $this->lastName;
    }

    public function getEmail():string
    {
        return $this->email;

    }
    public function getPassword():string
    {
        return $this->password;
    }
    public function getDni():int
    {
        return $this->dni;
    }

    public function getRoleId():?int
    {
        return $this->roleId;
    }
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email',
            'password'  => 'required|min_length[8]',
            'dni'       => 'required|is_natural_no_zero',
            'roleId'    => 'required|in_list[1,2,3,4]',
        ];
    }

    public static function messages(): array
    {
        return [
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
            'email' => [
                'required'    => 'Ingresá un email.',
                'valid_email' => 'Ese email no es válido.',
            ],
            'password' => [
                'required'   => 'Ingresá una contraseña.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
            'dni' => [
                'required'           => 'Ingresá el DNI.',
                'is_natural_no_zero' => 'El DNI no es válido.',
            ],
            'roleId' => [
                'required' => 'Elegí un rol.',
                'in_list'  => 'Elegí un rol válido.',
            ],
        ];
    }



}

