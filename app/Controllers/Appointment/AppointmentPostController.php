<?php

namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Services\Appointment\AppointmentCreatorService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentPostController extends ResourceController
{
    private AppointmentCreatorService $appointmentCreatorService;

    public function __construct()
    {
        $this->appointmentCreatorService = new AppointmentCreatorService();
    }

    public function create(): ResponseInterface
    {
        $appointmentRequest = $this->getRequest();
        $appointmentResponse = $this->appointmentCreatorService->create($appointmentRequest);

        return $this->response->setJSON($appointmentResponse);
    }

    private function getRequest(): AppointmentRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, AppointmentRequest::rules(), AppointmentRequest::messages());

        $parameters = (object) $parameters;

        return new AppointmentRequest(
            medicalId: (int) ($parameters->medicalId ?? 0),
            patientId: (int) ($parameters->patientId ?? 0),
            serviceId: (int) ($parameters->serviceId ?? 0),
            fecha: (string) ($parameters->fecha ?? ''),
            hora: (string) ($parameters->hora ?? ''),
            nota: $parameters->nota ?? null,
            status: $parameters->status ?? 'pending',
        );
    }
}