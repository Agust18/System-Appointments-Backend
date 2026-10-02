<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Services\Patient\PatientUpdaterService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPutController extends ResourceController
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
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PatientUpdateRequest::rules(), PatientUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new PatientUpdateRequest(
            $parameters->birthDate ?? null,
            $parameters->firstName ?? null,
            $parameters->lastName ?? null,
            $parameters->phone ?? null,
            $parameters->email ?? null,
        );
    }
}
