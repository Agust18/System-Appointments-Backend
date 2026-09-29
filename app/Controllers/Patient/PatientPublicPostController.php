<?php

namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientPublicRegisterRequest;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPublicPostController extends ResourceController
{
    private PatientCreatorService $patientCreatorService;

    public function __construct()
    {
        $this->patientCreatorService = new PatientCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientPublicRegisterRequest = $this->getRequest();

        $patientResponse = $this->patientCreatorService->create(
            $patientPublicRegisterRequest->toPatientRequest(),false
        );

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientPublicRegisterRequest
    {
        $parameters = $this->request->getJSON();

        return new PatientPublicRegisterRequest(
            firstName: $parameters->firstName,
            lastName: $parameters->lastName,
            email: $parameters->email,
            password: $parameters->password,
            dni: $parameters->dni,
            birthDate: $parameters->birthDate ?? null,
           
           
        );
    }
}