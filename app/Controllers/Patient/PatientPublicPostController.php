<?php

namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientSelfRegisterRequest;
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

    private function getRequest(): PatientSelfRegisterRequest
    {
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PatientSelfRegisterRequest::rules(), PatientSelfRegisterRequest::messages());

        $parameters = (object) $parameters;

        return new PatientSelfRegisterRequest(
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