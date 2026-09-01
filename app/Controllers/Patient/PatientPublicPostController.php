<?php

namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientPublicRegisterRequest;
use App\Services\Patient\PatientQuickCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PatientPublicPostController extends ResourceController
{
    private PatientQuickCreatorService $patientQuickCreatorService;

    public function __construct()
    {
        $this->patientQuickCreatorService = new PatientQuickCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientPublicRegisterRequest = $this->getRequest();

        $patientResponse = $this->patientQuickCreatorService->create(
            $patientPublicRegisterRequest->toPatientQuickRequest()
        );

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientPublicRegisterRequest
    {
        $parameters = $this->request->getJSON();

        return new PatientPublicRegisterRequest(
            username: $parameters->username,
            email: $parameters->email,
            password: $parameters->password,
            dni: $parameters->dni,
            birthDate: $parameters->birthDate ?? null,
           
           
        );
    }
}