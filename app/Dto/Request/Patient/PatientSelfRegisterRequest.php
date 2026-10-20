<?php

namespace App\Dto\Request\Patient;

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Validation\IdentityRules;

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
            'firstName' => IdentityRules::required(IdentityRules::FIRST_NAME),
            'lastName'  => IdentityRules::required(IdentityRules::LAST_NAME),
            'email'     => IdentityRules::required(IdentityRules::EMAIL),
            'phone'     => IdentityRules::optional(IdentityRules::PHONE),
            'password'  => IdentityRules::required(IdentityRules::PASSWORD),
            'dni'       => IdentityRules::required(IdentityRules::DNI),
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
        ];
    }

    public static function messages(): array
    {
        return array_merge(IdentityRules::messages(IdentityRules::SELF), [
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ]);
    }
}