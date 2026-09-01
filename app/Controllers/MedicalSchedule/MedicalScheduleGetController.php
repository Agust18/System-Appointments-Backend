<?php
namespace App\Controllers\MedicalSchedule;

use App\Services\MedicalSchedule\Projection\MedicalScheduleProjectionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalScheduleGetController extends ResourceController
{
    private MedicalScheduleProjectionFinderService $finderService;

    public function __construct()
    {
        $this->finderService = new MedicalScheduleProjectionFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $response = $this->finderService->findResponse($id);
        return $this->response->setJSON($response);
    }

    public function findByMedical(int $medicalId): ResponseInterface
    {
        $responses = $this->finderService->findByMedicalId($medicalId);
        return $this->response->setJSON($responses);
    }
}