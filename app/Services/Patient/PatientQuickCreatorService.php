<?php

namespace App\Services\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientQuickRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Services\User\UserCreatorService;
use App\Services\User\UserVerificationAccountService;
use CodeIgniter\Database\BaseConnection;

final class PatientQuickCreatorService
{
    private UserCreatorService $userCreatorService;
    private PatientCreatorService $patientCreatorService;
    private UserVerificationAccountService $verificationService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->userCreatorService = new UserCreatorService();
        $this->patientCreatorService = new PatientCreatorService();
        $this->verificationService = new UserVerificationAccountService();
        $this->db = \Config\Database::connect();
    }

    public function create(PatientQuickRequest $request): PatientResponse
    {
        $this->db->transStart();
        try {
            $newUser = $this->userCreatorService->create($request->getUserRequest());

            $patientRequest = $this->buildPatientRequest(
                $newUser->id,
                $request->getPatientDataRequest()
            );
            $result = $this->patientCreatorService->create($patientRequest);

            $token = $this->verificationService->generateEmailVerificationToken($newUser->id);

            $this->db->transComplete();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $this->verificationService->sendVerificationEmail($newUser->id, $token);

        return $result;
    }

    private function buildPatientRequest(int $userId, PatientDataRequest $data): PatientRequest
    {
        return new PatientRequest(
            userId: $userId,
            birthDate: $data->getBirthDate()
        );
    }
}