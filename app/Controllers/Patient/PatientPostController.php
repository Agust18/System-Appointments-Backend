<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPostController extends ResourceController
{
    private PatientCreatorService $patientCreatorService;

    public function __construct()
    {
        $this->patientCreatorService = new PatientCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientRequest = $this->getRequest();
        $patientResponse = $this->patientCreatorService->create($patientRequest, true);

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];
        $parameters['user'] = IdentityNormalizer::normalizeInput($parameters['user'] ?? []);

        $parameters['patient'] ??= [];

        // Recepción puede dar de alta un paciente sin contraseña: nace sin cuenta
        // y la activa después con el link de "olvidé mi contraseña".
        RequestValidator::validate($parameters['user'], UserRequest::rules(passwordRequired: false), UserRequest::messages());
        RequestValidator::validate($parameters['patient'], PatientDataRequest::rules(), PatientDataRequest::messages());

        $parameters = (object) $parameters;
        $parameters->user = (object) $parameters->user;
        $parameters->patient = (object) $parameters->patient;

        $userRequest = new UserRequest(
            $parameters->user->firstName,
            $parameters->user->lastName,
            $parameters->user->email,
            $parameters->user->phone ?? null,
            ($parameters->user->password ?? null) ?: null,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId ?? null,
        );

        $patientDataRequest = new PatientDataRequest(
            $parameters->patient->birthDate ?? null,
        );

        return new PatientRequest($userRequest, $patientDataRequest);
    }
}
