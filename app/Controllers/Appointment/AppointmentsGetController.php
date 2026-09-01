<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Services\Appointment\AppointmentFilterBuilderService;
use App\Services\Appointment\Projection\AppointmentsProjectionSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class AppointmentsGetController extends ResourceController
{
    private AppointmentsProjectionSearcherService $appointmentsProjectionSearcherService;
    private AppointmentFilterBuilderService $filterBuilderService;

    public function __construct()
    {
        $this->appointmentsProjectionSearcherService = new AppointmentsProjectionSearcherService();
        $this->filterBuilderService = new AppointmentFilterBuilderService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->appointmentsProjectionSearcherService->searchResponses($request);
        return $this->response->setJSON($responses);
    }

    private function getRequest(): AppointmentFilterRequest
    {
        return $this->filterBuilderService->build(
            new PaginationRequest(
                $this->request->getGet('page') ? (int)$this->request->getGet('page') : 1,
                $this->request->getGet('size') ? (int)$this->request->getGet('size') : 10,
            ),
            $this->request->getGet('medicalId') ? (int)$this->request->getGet('medicalId') : null,
            $this->request->getGet('patientId') ? (int)$this->request->getGet('patientId') : null,
            $this->request->getGet('status') ?? null,
            $this->request->getGet('fecha') ?? null,
            $this->request->getGet('search') ?? null
        );
    }
}