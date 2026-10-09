<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsRecentAppointmentsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsRecentAppointmentsGetController extends ResourceController
{
    private MetricsRecentAppointmentsService $metricsRecentAppointmentsService;

    public function __construct()
    {
        $this->metricsRecentAppointmentsService = new MetricsRecentAppointmentsService();
    }

    public function get(): ResponseInterface
    {
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null,
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $responses = $this->metricsRecentAppointmentsService->get($request);

        return $this->response->setJSON($responses);
    }
}
