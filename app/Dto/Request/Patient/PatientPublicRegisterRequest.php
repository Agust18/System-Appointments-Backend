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
}