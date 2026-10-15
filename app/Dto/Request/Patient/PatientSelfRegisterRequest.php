<?php

namespace App\Dto\Request\Patient;

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;

final class PatientSelfRegisterRequest
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly string $dni,
        public readonly ?string $phone = null,
        public readonly ?string $birthDate = null,
    ) {}

    
    public function toPatientRequest(): PatientRequest
    {
        $userRequest = new UserRequest(
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->phone ?? null,
            $this->password,
            $this->dni,
            true,
            Role::PATIENT
        );

        $patientDataRequest = new PatientDataRequest($this->birthDate);

        return new PatientRequest($userRequest, $patientDataRequest);
    }

    public static function rules(): array
    {
        return [
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email|max_length[150]',
            'phone'     => 'permit_empty|is_natural|min_length[10]|max_length[15]',
            'password'  => 'required|min_length[8]',
            'dni'       => 'required|is_natural_no_zero|min_length[7]|max_length[8]',
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
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
                'is_natural' => 'El teléfono solo puede tener números.',
                'min_length' => 'El teléfono es muy corto.',
                'max_length' => 'El teléfono es muy largo.',
            ],
            'password' => [
                'required'   => 'Ingresá una contraseña.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
            'dni' => [
                'required'           => 'Ingresá tu DNI.',
                'is_natural_no_zero' => 'El DNI debe ser numérico.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ];
    }
}