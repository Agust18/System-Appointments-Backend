<?php
namespace App\Dto\Request\User;

final readonly class UserUpdaterRequest {
    public function __construct(
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $password = null,
        private ?int $dni = null,
        private ?bool $isActive = null,
        private ?int $roleId = null


    )
    {}
    public function getPassword():?string
    {
        return $this->password;
    }
    public function getDni():?int
    {
        return $this->dni;
    }
    public function getIsActive():?bool
    {
        return $this->isActive;
    }

    public function getEmail():?string
    {
        return $this->email;
    }

    public function getPhone():?string
    {
        return $this->phone;
    }
    public function getFirstName():?string
    {
        return $this->firstName;
    }

    public function getLastName():?string
    {
        return $this->lastName;
    }

     public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public static function rules(): array
    {
        return [
            'firstName' => 'permit_empty|min_length[2]|max_length[100]',
            'lastName'  => 'permit_empty|min_length[2]|max_length[100]',
            'email'     => 'permit_empty|valid_email',
            'phone'     => 'permit_empty|is_natural|min_length[10]|max_length[15]',
            'password'  => 'permit_empty|min_length[8]',
            'dni'       => 'permit_empty|is_natural_no_zero|min_length[7]|max_length[8]',
            'isActive'  => 'permit_empty|in_list[0,1]',
            'roleId'    => 'permit_empty|in_list[1,2,3,4]',
        ];
    }

    public static function messages(): array
    {
        return [
            'firstName' => [
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'lastName' => [
                'min_length' => 'El apellido es muy corto.',
                'max_length' => 'El apellido es muy largo.',
            ],
            'email' => [
                'valid_email' => 'Ese email no es válido.',
            ],
            'phone' => [
                'is_natural' => 'El teléfono solo puede tener números.',
                'min_length' => 'El número de teléfono es muy corto.',
                'max_length' => 'El número de teléfono es muy largo.',
            ],
            'password' => [
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
            'dni' => [
                'is_natural_no_zero' => 'El DNI no es válido.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
            'roleId' => [
                'in_list' => 'Elegí un rol válido.',
            ],
        ];
    }
}
