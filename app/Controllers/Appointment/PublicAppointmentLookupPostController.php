<?php
namespace App\Controllers\Appointment;

use App\Services\Appointment\PublicAppointmentLookupService;
use CodeIgniter\HTTP\ResponseInterface;

final class PublicAppointmentLookupPostController extends PublicAppointmentAccessController
{
    private PublicAppointmentLookupService $lookupService;

    public function __construct()
    {
        $this->lookupService = new PublicAppointmentLookupService();
    }

    public function find(): ResponseInterface
    {
        return $this->response->setJSON($this->lookupService->find($this->getAccessRequest()));
    }
}
