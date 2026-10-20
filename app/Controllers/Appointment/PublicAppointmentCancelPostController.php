<?php
namespace App\Controllers\Appointment;

use App\Services\Appointment\PublicAppointmentCancellerService;
use CodeIgniter\HTTP\ResponseInterface;

final class PublicAppointmentCancelPostController extends PublicAppointmentAccessController
{
    private PublicAppointmentCancellerService $cancellerService;

    public function __construct()
    {
        $this->cancellerService = new PublicAppointmentCancellerService();
    }

    public function do(): ResponseInterface
    {
        return $this->response->setJSON($this->cancellerService->cancel($this->getAccessRequest()));
    }
}
