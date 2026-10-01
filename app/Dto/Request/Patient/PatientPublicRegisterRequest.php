<?php

namespace App\Dto\Request\Patient;

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;

final class PatientPublicRegisterRequest
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly string $dni,
        public readonly ?string $birthDate = null,
   
    ) {}

    
    public function toPatientRequest(): PatientRequest
    {
        $userRequest = new UserRequest(
            $this->firstName,
            $this->lastName,
            $this->email,
            null,
            $this->password,
            $this->dni,
            true,
            Role::PATIENT
        );

        $patientDataRequest = new PatientDataRequest(
            $this->birthDate,

        );

        return new PatientRequest($userRequest, $patientDataRequest);
    }

    public static function rules(): array
    {
        return [
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email',
            'password'  => 'required|min_length[8]',
            'dni'       => 'required|numeric|exact_length[8]',
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
            ],
            'password' => [
                'required'   => 'Ingresá una contraseña.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
            'dni' => [
                'required'     => 'Ingresá tu DNI.',
                'numeric'      => 'El DNI debe ser numérico.',
                'exact_length' => 'El DNI debe tener 8 dígitos.',
            ],
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ];
    }
}