<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Services\Appointment\AppointmentCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AppointmentPostController extends ResourceController
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
        $parameters = $this->request->getJSON();
    
        return new AppointmentRequest(
            (int) $parameters->medicalId,
            (int) $parameters->patientId,
            $parameters->fecha,
            $parameters->hora,
            $parameters->nota,
            $parameters->status
        );
    }
}