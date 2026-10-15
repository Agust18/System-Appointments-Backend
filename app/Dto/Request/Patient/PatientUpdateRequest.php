<?php
namespace App\Dto\Request\Patient;

use App\Dto\Request\User\UserUpdaterRequest;

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

    /**
     * Datos de contacto del paciente, que viven en users. Devuelve null si el
     * request no trae ninguno, para no tocar al usuario.
     */
    public function toUserUpdaterRequest(): ?UserUpdaterRequest
    {
        if ($this->firstName === null && $this->lastName === null && $this->phone === null && $this->email === null) {
            return null;
        }

        return new UserUpdaterRequest(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            phone: $this->phone,
        );
    }

    public static function rules(): array
    {
        return [
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
            'firstName' => 'permit_empty|min_length[2]|max_length[100]',
            'lastName'  => 'permit_empty|min_length[2]|max_length[100]',
            'phone'     => 'permit_empty|is_natural|min_length[10]|max_length[15]',
            'email'     => 'permit_empty|valid_email|max_length[150]',
        ];
    }

    public static function messages(): array
    {
        return [
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
            'firstName' => [
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'lastName' => [
                'min_length' => 'El apellido es muy corto.',
                'max_length' => 'El apellido es muy largo.',
            ],
            'phone' => [
                'is_natural' => 'El teléfono solo puede tener números.',
                'min_length' => 'El número de teléfono es muy corto.',
                'max_length' => 'El número de teléfono es muy largo.',
            ],
            'email' => [
                'valid_email' => 'Ese email no es válido.',
                'max_length'  => 'El email es muy largo.',
            ],
        ];
    }
}
