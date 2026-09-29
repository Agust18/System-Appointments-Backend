<?php
namespace App\Dto\Request\Medical;

use App\Dto\Request\User\UserRequest;

final readonly class MedicalRequest
{
    public function __construct(
        private UserRequest $userRequest,
        private MedicalDataRequest $medicalDataRequest,
    ) {}

    public function getUserRequest(): UserRequest
    {
        return $this->userRequest;
    }

    public function getMedicalDataRequest(): MedicalDataRequest
    {
        return $this->medicalDataRequest;
    }
}
