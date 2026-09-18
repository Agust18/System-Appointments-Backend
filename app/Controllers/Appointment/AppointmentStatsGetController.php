<?php

namespace App\Controllers\Appointment;

use App\Services\Appointment\AppointmentStatsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentStatsGetController extends ResourceController
{
    private AppointmentStatsService $statsService;

    public function __construct()
    {
        $this->statsService = new AppointmentStatsService();
    }

    public function get(): ResponseInterface
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $fecha = $this->request->getGet('fecha');

        $stats = $this->statsService->getStats($roleId, $userId, $fecha);

        return $this->response->setJSON($stats);
    }
}
