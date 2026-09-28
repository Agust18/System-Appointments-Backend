<?php

namespace App\Services\Patient;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientQuickRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Services\auth\SendVerificationEmailService;
use App\Services\User\UserCreatorService;
use App\Services\User\UserFinderService;
use CodeIgniter\Database\BaseConnection;
use Exception;

final class PatientQuickCreatorService
{
    private UserCreatorService $userCreatorService;
    private PatientCreatorService $patientCreatorService;
    private SendVerificationEmailService $emailVerificationService;
    private UserFinderService $userFinderService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->userCreatorService = new UserCreatorService();
        $this->patientCreatorService = new PatientCreatorService();
        $this->emailVerificationService = new SendVerificationEmailService();
        $this->userFinderService = new UserFinderService();
        $this->db = \Config\Database::connect();
    }

    public function create(PatientQuickRequest $request, bool $isVerified = false): PatientResponse
    {
        $requestedRole = $request->getUserRequest()->getRoleId();

        if ($requestedRole === null) {
            throw new Exception('El campo roleId es requerido', 400);
        }
        if ($requestedRole !== Role::PATIENT) {
            throw new Exception('El rol no coincide con el tipo de recurso que se quiere crear', 403);
        }

        $this->db->transStart();

        $newUser = $this->userCreatorService->create($request->getUserRequest(), $isVerified);

        $patientRequest = $this->buildPatientRequest(
            $newUser->id,
            $request->getPatientDataRequest()
        );
        $result = $this->patientCreatorService->create($patientRequest);

        $this->db->transComplete();

        if (!$isVerified) {
            $this->emailVerificationService->send(
                $this->userFinderService->find($newUser->id)
            );

        }

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
