<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Services\Appointment\AppointmentUpdaterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AppointmentPutController extends ResourceController
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
        $parameters = $this->request->getJSON();

        return new AppointmentUpdateRequest(
            $parameters->medicalId ?? null,
            $parameters->patientId ?? null,
            $parameters->serviceId ?? null,
            $parameters->fecha ?? null,
            $parameters->hora ?? null,
            $parameters->nota ?? null,
            $parameters->status ?? null
        );
    }
}