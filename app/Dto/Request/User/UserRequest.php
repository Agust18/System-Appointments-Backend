<?php
namespace App\Dto\Request\User;

use App\Constants\Role;
use App\Validation\IdentityRules;

final readonly class UserRequest{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private ?string $email,
        private ?string $phone,
        private ?string $password,
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

    /**
     * Null solo en el alta de pacientes por panel (PatientPostController): el resto
     * de los flujos exige el email en sus reglas.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getPassword(): ?string
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

    /**
     * @param bool $passwordRequired false en el alta de pacientes por recepción:
     *                               sin contraseña nace un usuario sin cuenta.
     */
    public static function rules(bool $passwordRequired = true): array
    {
        return [
            'firstName' => IdentityRules::required(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::required(IdentityRules::LAST_NAME),
            'email'     => IdentityRules::required(IdentityRules::EMAIL),
            'phone'     => IdentityRules::optional(IdentityRules::PHONE),
            'password'  => $passwordRequired
                ? IdentityRules::required(IdentityRules::PASSWORD)
                : IdentityRules::optional(IdentityRules::PASSWORD),
            'dni'       => IdentityRules::required(IdentityRules::DNI),
            'roleId'    => 'required|' . Role::inListRule(),
        ];
    }

    public static function messages(): array
    {
        return array_merge(IdentityRules::messages(), [
            'roleId' => [
                'required' => 'Elegí un rol.',
                'in_list'  => 'Elegí un rol válido.',
            ],
        ]);
    }
}

