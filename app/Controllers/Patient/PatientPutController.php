<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Services\Patient\PatientUpdaterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PatientPutController extends ResourceController
{
    private PatientUpdaterService $patientUpdaterService;

    public function __construct()
    {
        $this->patientUpdaterService = new PatientUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $patientResponse = $this->patientUpdaterService->update($request, $id);
        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientUpdateRequest
    {
        $parameters = $this->request->getJSON();
        
        return new PatientUpdateRequest(
            $parameters->birthDate ?? null,
              
        );
    }
}