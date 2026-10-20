<?php
namespace App\Dto\Request\Patient;

use App\Validation\IdentityRules;

final readonly class PatientUpdateRequest
{
    public function __construct(
        private ?string $birthDate,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $phone = null,
        private ?string $email = null,
    ) {}

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public static function rules(): array
    {
        return [
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
            'firstName' => IdentityRules::optional(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::optional(IdentityRules::LAST_NAME),
            'phone'     => IdentityRules::optional(IdentityRules::PHONE),
            'email'     => IdentityRules::optional(IdentityRules::EMAIL),
        ];
    }

    public static function messages(): array
    {
        return array_merge(IdentityRules::messages(), [
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ]);
    }
}
