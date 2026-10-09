<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsTopServicesService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsTopServicesGetController extends ResourceController
{
    private MetricsTopServicesService $metricsTopServicesService;

    public function __construct()
    {
        $this->metricsTopServicesService = new MetricsTopServicesService();
    }

    public function get(): ResponseInterface
    {
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null,
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $responses = $this->metricsTopServicesService->get($request);

        return $this->response->setJSON($responses);
    }
}
