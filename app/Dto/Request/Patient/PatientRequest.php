<?php
namespace App\Dto\Request\Patient;

use App\Validation\IdentityRules;

final readonly class PatientRequest
{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private ?string $email,
        private ?string $phone,
        private int $dni,
        private ?string $birthDate = null,
    ) {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getDni(): int
    {
        return $this->dni;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public static function fromGuest(GuestPatientRequest $guest): self
    {
        return new self(
            $guest->getFirstName(),
            $guest->getLastName(),
            $guest->getEmail(),
            $guest->getPhone(),
            $guest->getDni(),
        );
    }

    /**
     * El teléfono es obligatorio (recepción lo usa para contactar al paciente); el email es opcional:
     * hay pacientes que no tienen y a ellos no se les envía ningún aviso.
     */
    public static function rules(): array
    {
        return [
            'firstName' => IdentityRules::required(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::required(IdentityRules::LAST_NAME),
            'email'     => IdentityRules::optional(IdentityRules::EMAIL),
            'phone'     => IdentityRules::required(IdentityRules::PHONE),
            'dni'       => IdentityRules::required(IdentityRules::DNI),
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
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
