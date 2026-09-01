<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Services\Appointment\AppointmentAvailabilityService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentAvailabilityGetController extends ResourceController
{
    private AppointmentAvailabilityService $availabilityService;

    public function __construct()
    {
        $this->availabilityService = new AppointmentAvailabilityService();
    }

    /**
     * Devuelve los horarios/slots disponibles para reservar.
     * GET /appointments/availability?medicalId=1&serviceId=2&fecha=2026-09-01
     */
    public function getAvailability(): ResponseInterface
    {
        $request = $this->getRequest();
        $slots = $this->availabilityService->getAvailableSlots($request);

        return $this->response->setJSON($slots);
    }

    private function getRequest(): AppointmentAvailabilityRequest
    {
        return new AppointmentAvailabilityRequest(
            (int)$this->request->getGet('medicalId'),
            (int)$this->request->getGet('serviceId'),
            (string)$this->request->getGet('fecha')
        );
    }
}