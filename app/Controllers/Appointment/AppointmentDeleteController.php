<?php

namespace App\Controllers\Appointment;

use App\Services\Appointment\AppointmentDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AppointmentDeleteController extends ResourceController
{
    private AppointmentDeleterService $appointmentDeleterService;

    public function __construct()
    {
        $this->appointmentDeleterService = new AppointmentDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
       $this->appointmentDeleterService->delete($id);

        return $this->response->setJSON([
            "id"  => $id
           
        ]);
    }
}
