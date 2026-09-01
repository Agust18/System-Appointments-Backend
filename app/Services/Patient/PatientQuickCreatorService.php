<?php
namespace App\Services\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientQuickRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Services\User\UserCreatorService;
use CodeIgniter\Database\BaseConnection;

final class PatientQuickCreatorService
{
    private UserCreatorService $userCreatorService;
    private PatientCreatorService $patientCreatorService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->userCreatorService = new UserCreatorService();
        $this->patientCreatorService = new PatientCreatorService();
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

            $this->db->transComplete();

            return $result;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function buildPatientRequest(int $userId, PatientDataRequest $data): PatientRequest
    {
        return new PatientRequest(
            userId: $userId,
            birthDate: $data->getBirthDate()
          
           
        );
    }
}