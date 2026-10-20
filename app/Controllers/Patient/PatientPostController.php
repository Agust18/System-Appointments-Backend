<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientRequest;
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
        $patientResponse = $this->patientCreatorService->create($this->getRequest());

        return $this->response->setJSON($patientResponse);
    }

    /**
     * Body plano: `firstName, lastName, email?, phone, dni, birthDate?`. El paciente no tiene cuenta:
     * cualquier otro campo (rol, contraseña, etc.) se ignora.
     */
    private function getRequest(): PatientRequest
    {
        $data = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($data, PatientRequest::rules(), PatientRequest::messages());

        return new PatientRequest(
            $data['firstName'],
            $data['lastName'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            (int) $data['dni'],
            $data['birthDate'] ?? null,
        );
    }
}
