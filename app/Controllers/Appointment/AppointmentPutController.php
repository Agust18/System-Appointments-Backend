<?php

namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Services\Appointment\AppointmentUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentPutController extends ResourceController
{
    private AppointmentUpdaterService $appointmentUpdaterService;

    public function __construct()
    {
        $this->appointmentUpdaterService = new AppointmentUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->appointmentUpdaterService->update($request, $id);
        return $this->response->setJSON($response);
    }

    private function getRequest(): AppointmentUpdateRequest
    {

        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, AppointmentUpdateRequest::rules(), AppointmentUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new AppointmentUpdateRequest(
            medicalId: isset($parameters->medicalId) ? (int) $parameters->medicalId : null,
            patientId: isset($parameters->patientId) ? (int) $parameters->patientId : null,
            serviceId: isset($parameters->serviceId) ? (int) $parameters->serviceId : null,
            fecha: isset($parameters->fecha) ? (string) $parameters->fecha : null,
            hora: isset($parameters->hora) ? (string) $parameters->hora : null,
            nota: $parameters->nota ?? null,
            status: $parameters->status ?? null
        );
    }
}
