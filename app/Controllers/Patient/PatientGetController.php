<?php
namespace App\Controllers\Patient;

use App\Services\Patient\Projection\PatientProjectionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientGetController extends ResourceController
{
    private PatientProjectionFinderService $patientProjectionFinderService;

    public function __construct()
    {
        $this->patientProjectionFinderService = new PatientProjectionFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $patientResponse = $this->patientProjectionFinderService->findResponse($id);
        return $this->response->setJSON($patientResponse);
    }

    public function findByUser(int $userId): ResponseInterface
    {
        $patientResponse = $this->patientProjectionFinderService->findByUserIdResponse($userId);
        return $this->response->setJSON($patientResponse);
    }
}