<?php

namespace App\Controllers\Appointment;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\Appointment\AppointmentFinderService;

class AppointmentGetController extends ResourceController
{

    private AppointmentFinderService $appointmentFinderService;

    public function __construct()
    {
        $this->appointmentFinderService = new AppointmentFinderService();
    }
    public function find(int $id): ResponseInterface
    {
        $appointment = $this->appointmentFinderService->findResponse($id);
        return $this->response->setJSON($appointment);
    }
}
