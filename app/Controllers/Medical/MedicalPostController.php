<?php
namespace App\Controllers\Medical;

use App\Dto\Request\Medical\MedicalRequest;
use App\Services\Medical\MedicalCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalPostController extends ResourceController
{
    private MedicalCreatorService $medicalCreatorService;

    public function __construct()
    {
        $this->medicalCreatorService = new MedicalCreatorService();
    }

    public function create(): ResponseInterface
    {
        $medicalRequest = $this->getRequest();
        $medicalResponse = $this->medicalCreatorService->create($medicalRequest);
        
        return $this->response->setJSON($medicalResponse);
    }

    private function getRequest(): MedicalRequest
    {
        $parameters = $this->request->getJSON();
    
        return new MedicalRequest(
            $parameters->userId,
            $parameters->tuition,
            $parameters->specialtyId,
            $parameters->phone ?? null,
            $parameters->isActive ?? true
        );
    }
}