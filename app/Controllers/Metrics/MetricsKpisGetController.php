<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsKpisService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsKpisGetController extends ResourceController
{
    private MetricsKpisService $metricsKpisService;

    public function __construct()
    {
        $this->metricsKpisService = new MetricsKpisService();
    }

    public function get(): ResponseInterface
    {
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null,
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $response = $this->metricsKpisService->get($request);

        return $this->response->setJSON($response);
    }
}
