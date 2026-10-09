<?php

namespace App\Controllers\Appointment;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\Appointment\Projection\AppointmentProjectionFinderService;

final class AppointmentGetController extends ResourceController
{

    private AppointmentProjectionFinderService $appointmentProjectionFinderService;

    public function __construct()
    {
        $this->appointmentProjectionFinderService = new AppointmentProjectionFinderService();
    }
    public function find(int $id): ResponseInterface
    {
        $appointment = $this->appointmentProjectionFinderService->findResponse($id);
        return $this->response->setJSON($appointment);
    }
}
