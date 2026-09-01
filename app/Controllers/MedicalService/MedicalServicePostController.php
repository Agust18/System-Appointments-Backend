<?php
namespace App\Controllers\MedicalService;

use App\Dto\Request\MedicalService\MedicalServiceRequest;
use App\Services\MedicalService\MedicalServiceAssignmentService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalServicePostController extends ResourceController
{
    private MedicalServiceAssignmentService $medicalServiceAssignmentService;

    public function __construct()
    {
        $this->medicalServiceAssignmentService = new MedicalServiceAssignmentService();
    }
    
    public function assignService(int $medicalId): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->medicalServiceAssignmentService->assign($request, $medicalId);

        return $this->response->setJSON($response);
    }

    private function getRequest(): MedicalServiceRequest
    {
        $parameters = $this->request->getJSON();

        return new MedicalServiceRequest(
            $parameters->serviceId,
            $parameters->customPrice ?? null,
            $parameters->customDurationMinutes ?? null,
            $parameters->isActive ?? true
        );
    }
}