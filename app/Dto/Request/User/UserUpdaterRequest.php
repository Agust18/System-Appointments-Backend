<?php
namespace App\Dto\Request\User;

use App\Validation\IdentityRules;

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
            'firstName' => IdentityRules::optional(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::optional(IdentityRules::LAST_NAME),
            'email'     => IdentityRules::optional(IdentityRules::EMAIL),
            'phone'     => IdentityRules::optional(IdentityRules::PHONE),
            'password'  => IdentityRules::optional(IdentityRules::PASSWORD),
            'dni'       => IdentityRules::optional(IdentityRules::DNI),
            'isActive'  => 'permit_empty|in_list[0,1]',
            'roleId'    => 'permit_empty|in_list[1,2,3,4]',
        ];
    }

    public static function messages(): array
    {
        return array_merge(IdentityRules::messages(), [
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
            'roleId' => [
                'in_list' => 'Elegí un rol válido.',
            ],
        ]);
    }
}
