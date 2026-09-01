<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientRequest;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PatientPostController extends ResourceController
{
    private PatientCreatorService $patientCreatorService;

    public function __construct()
    {
        $this->patientCreatorService = new PatientCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientRequest = $this->getRequest();
        $patientResponse = $this->patientCreatorService->create($patientRequest);
        
        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientRequest
    {
        $parameters = $this->request->getJSON();
        
        return new PatientRequest(
            $parameters->userId,
            $parameters->birthDate,
          
           
        );
    }
}