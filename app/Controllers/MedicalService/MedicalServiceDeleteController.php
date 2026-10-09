<?php
namespace App\Controllers\MedicalService;

use App\Services\MedicalService\MedicalServiceDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalServiceDeleteController extends ResourceController
{
    private MedicalServiceDeleterService $medicalServiceDeleterService;

    public function __construct()
    {
        $this->medicalServiceDeleterService = new MedicalServiceDeleterService();
    }

    public function do(int $medicalId, int $serviceId): ResponseInterface
    {
        $this->medicalServiceDeleterService->delete($medicalId, $serviceId);

        return $this->response->setJSON([
            "medicalId" => $medicalId,
            "serviceId" => $serviceId,
        ]);
    }
}