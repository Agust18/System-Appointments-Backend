<?php
namespace App\Services\Medical;

use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalQuickRequest;
use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Services\User\UserCreatorService;
use CodeIgniter\Database\BaseConnection;

final class MedicalQuickCreatorService
{
    private UserCreatorService $userCreatorService;
    private MedicalCreatorService $medicalCreatorService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->userCreatorService = new UserCreatorService();
        $this->medicalCreatorService = new MedicalCreatorService();
        $this->db = \Config\Database::connect();
    }

    public function create(MedicalQuickRequest $request): MedicalResponse
    {
        $this->db->transStart();

        try {
            $newUser = $this->userCreatorService->create($request->getUserRequest());

            $medicalRequest = $this->buildMedicalRequest(
                $newUser->id,
                $request->getMedicalDataRequest()
            );

            $result = $this->medicalCreatorService->create($medicalRequest);

            $this->db->transComplete();

            return $result;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function buildMedicalRequest(int $userId, MedicalDataRequest $data): MedicalRequest
    {
        return new MedicalRequest(
            userId: $userId,
            tuition: $data->getTuition(),
            specialtyId: $data->getSpecialtyId(),
            phone: $data->getPhone(),
            isActive: $data->getIsActive()
        );
    }
}