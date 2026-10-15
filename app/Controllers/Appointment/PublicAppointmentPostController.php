<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Services\Appointment\PublicAppointmentCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * POST /public/appointments (sin auth). Solo lee los campos del contrato: cualquier
 * `status` o `patientId` que venga en el body se ignora.
 */
final class PublicAppointmentPostController extends ResourceController
{
    private PublicAppointmentCreatorService $creatorService;

    public function __construct()
    {
        $this->creatorService = new PublicAppointmentCreatorService();
    }

    public function create(): ResponseInterface
    {
        $response = $this->creatorService->create($this->getRequest());

        return $this->response->setStatusCode(201)->setJSON($response);
    }

    private function getRequest(): PublicAppointmentRequest
    {
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PublicAppointmentRequest::rules(), PublicAppointmentRequest::messages());

        $parameters = (object) $parameters;

        return new PublicAppointmentRequest(
            new GuestPatientRequest(
                $parameters->firstName,
                $parameters->lastName,
                $parameters->email,
                $parameters->phone,
                (int) $parameters->dni
            ),
            (int) $parameters->medicalId,
            (int) $parameters->serviceId,
            $parameters->fecha,
            $parameters->hora,
            ($parameters->nota ?? null) ?: null
        );
    }
}
