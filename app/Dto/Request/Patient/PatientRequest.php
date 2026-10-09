<?php
namespace App\Dto\Request\Patient;

use App\Dto\Request\User\UserRequest;

final readonly class PatientRequest
{
    public function __construct(
        private UserRequest $userRequest,
        private PatientDataRequest $patientDataRequest,
    ) {}

    public function getUserRequest(): UserRequest
    {
        return $this->userRequest;
    }

    public function getPatientDataRequest(): PatientDataRequest
    {
        return $this->patientDataRequest;
    }
}
