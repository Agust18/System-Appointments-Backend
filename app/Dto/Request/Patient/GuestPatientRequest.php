<?php
namespace App\Dto\Request\Patient;

use App\Validation\IdentityRules;

/**
 * Datos de identidad que declara quien reserva sin cuenta. Llegan ya
 * normalizados (IdentityNormalizer): el DNI y el teléfono solo con dígitos.
 */
final readonly class GuestPatientRequest
{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private string $email,
        private string $phone,
        private int $dni,
    ) {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getDni(): int
    {
        return $this->dni;
    }

    public static function rules(): array
    {
        return [
            'firstName' => IdentityRules::required(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::required(IdentityRules::LAST_NAME),
            'email'     => IdentityRules::required(IdentityRules::EMAIL),
            'phone'     => IdentityRules::required(IdentityRules::PHONE),
            'dni'       => IdentityRules::required(IdentityRules::DNI),
        ];
    }

    public static function messages(): array
    {
        return IdentityRules::messages(IdentityRules::SELF);
    }
}
