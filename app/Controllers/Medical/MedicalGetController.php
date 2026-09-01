<?php
namespace App\Controllers\Medical;

use App\Services\Medical\Projection\MedicalProjectionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalGetController extends ResourceController
{
    private MedicalProjectionFinderService $medicalFinderService;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalProjectionFinderService();
    }

    
    public function find(int $id): ResponseInterface
    {
        $medicalResponse = $this->medicalFinderService->findResponse($id);
        return $this->response->setJSON($medicalResponse);
    }

    
    public function findByUser(int $userId): ResponseInterface
    {
        $medicalResponse = $this->medicalFinderService->findByUserIdResponse($userId);
        return $this->response->setJSON($medicalResponse);
    }
}