<?php
namespace App\Dto\Request\Patient;

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
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email|max_length[150]',
            'phone'     => 'required|is_natural|min_length[10]|max_length[15]',
            'dni'       => 'required|is_natural_no_zero|min_length[7]|max_length[8]',
        ];
    }

    public static function messages(): array
    {
        return [
            'firstName' => [
                'required'   => 'Ingresá tu nombre.',
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'lastName' => [
                'required'   => 'Ingresá tu apellido.',
                'min_length' => 'El apellido es muy corto.',
                'max_length' => 'El apellido es muy largo.',
            ],
            'email' => [
                'required'    => 'Ingresá tu email.',
                'valid_email' => 'Ese email no es válido.',
                'max_length'  => 'El email es muy largo.',
            ],
            'phone' => [
                'required'   => 'Ingresá tu teléfono.',
                'is_natural' => 'El teléfono solo puede tener números.',
                'min_length' => 'El teléfono es muy corto.',
                'max_length' => 'El teléfono es muy largo.',
            ],
            'dni' => [
                'required'           => 'Ingresá tu DNI.',
                'is_natural_no_zero' => 'El DNI debe ser numérico.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
        ];
    }
}
