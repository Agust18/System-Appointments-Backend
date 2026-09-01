<?php
namespace App\Controllers\MedicalService;

use App\Services\MedicalService\Projection\MedicalServicesProjectionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalServiceGetController extends ResourceController
{
    private MedicalServicesProjectionFinderService $medicalServicesProjectionFinderService;

    public function __construct()
    {
        $this->medicalServicesProjectionFinderService = new MedicalServicesProjectionFinderService();
    }

    public function findByMedical(int $medicalId): ResponseInterface
    {
        $responses = $this->medicalServicesProjectionFinderService->findByMedicalResponses($medicalId);

        return $this->response->setJSON($responses);
    }
}