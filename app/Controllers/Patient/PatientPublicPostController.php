<?php

namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientPublicRegisterRequest;
use App\Services\Patient\PatientPublicRegisterService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPublicPostController extends ResourceController
{
    private PatientPublicRegisterService $patientPublicRegisterService;

    public function __construct()
    {
        $this->patientPublicRegisterService = new PatientPublicRegisterService();
    }

    public function create(): ResponseInterface
    {
        $patientPublicRegisterRequest = $this->getRequest();

        $patientResponse = $this->patientPublicRegisterService->register(
            $patientPublicRegisterRequest->toPatientRequest()
        );

        return $this->response->setStatusCode(201)->setJSON($patientResponse);
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