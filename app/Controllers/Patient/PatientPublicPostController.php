<?php

namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientPublicRegisterRequest;
use App\Services\Patient\PatientCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
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
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PatientPublicRegisterRequest::rules(), PatientPublicRegisterRequest::messages());

        $parameters = (object) $parameters;

        return new PatientPublicRegisterRequest(
            firstName: $parameters->firstName,
            lastName: $parameters->lastName,
            email: $parameters->email,
            phone: $parameters->phone ?? null,
            password: $parameters->password,
            dni: $parameters->dni,
            birthDate: $parameters->birthDate ?? null,
           
           
        );
    }
}