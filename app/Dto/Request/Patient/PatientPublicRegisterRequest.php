<?php

namespace App\Dto\Request\Patient;

use App\Constants\Role; 
use App\Dto\Request\User\UserRequest;

final class PatientPublicRegisterRequest
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $dni,
        public readonly ?string $birthDate = null,
   
    ) {}

    
    public function toPatientQuickRequest(): PatientQuickRequest
    {
        $userRequest = new UserRequest(
            $this->username,
            $this->email,
            $this->password,
            $this->dni,
            true,         
            Role::PATIENT  
        );

        $patientDataRequest = new PatientDataRequest(
            $this->birthDate,
          
        );

        return new PatientQuickRequest($userRequest, $patientDataRequest);
    }
}