<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentAvailabilityBatchRequest;
use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Services\Appointment\AppointmentAvailabilityBatchService;
use App\Services\Appointment\AppointmentAvailabilityService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentAvailabilityGetController extends ResourceController
{
    private AppointmentAvailabilityService $availabilityService;
    private AppointmentAvailabilityBatchService $availabilityBatchService;

    public function __construct()
    {
        $this->availabilityService = new AppointmentAvailabilityService();
        $this->availabilityBatchService = new AppointmentAvailabilityBatchService();
    }

    /**
     * Devuelve los horarios/slots disponibles para reservar.
     * - Un día:  GET /appointments/availability?medicalId=1&serviceId=2&fecha=2026-09-01
     * - Rango:   GET /appointments/availability?medicalId=1&serviceId=2&from=2026-09-01&to=2026-09-14
     */
    public function getAvailability(): ResponseInterface
    {
        if ($this->request->getGet('from') !== null || $this->request->getGet('to') !== null) {
            $batchRequest = $this->getBatchRequest();
            $response = $this->availabilityBatchService->getAvailableSlotsBatch($batchRequest);

            return $this->response->setJSON($response);
        }

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

    private function getBatchRequest(): AppointmentAvailabilityBatchRequest
    {
        return new AppointmentAvailabilityBatchRequest(
            (int)$this->request->getGet('medicalId'),
            (int)$this->request->getGet('serviceId'),
            (string)$this->request->getGet('from'),
            (string)$this->request->getGet('to')
        );
    }
}
