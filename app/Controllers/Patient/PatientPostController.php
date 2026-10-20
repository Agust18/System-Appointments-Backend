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
     * Mantiene el formato `{user: {...}, patient: {...}}` mientras el frontend no migra: `roleId`,
     * `password` e `isActive` del bloque `user` se ignoran (el paciente no tiene cuenta).
     */
    private function getRequest(): PatientRequest
    {
        $body = $this->request->getJSON(true) ?? [];
        $identity = IdentityNormalizer::normalizeInput($body['user'] ?? []);
        $data = array_merge($identity, ['birthDate' => ($body['patient'] ?? [])['birthDate'] ?? null]);

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
