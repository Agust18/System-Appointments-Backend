<?php

namespace App\Controllers\Appointment;

use App\Services\Appointment\AppointmentAccessCodeRegeneratorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentAccessCodePostController extends ResourceController
{
    private AppointmentAccessCodeRegeneratorService $regeneratorService;

    public function __construct()
    {
        $this->regeneratorService = new AppointmentAccessCodeRegeneratorService();
    }

    public function do(int $id): ResponseInterface
    {
        return $this->response->setJSON($this->regeneratorService->regenerate($id));
    }
}
