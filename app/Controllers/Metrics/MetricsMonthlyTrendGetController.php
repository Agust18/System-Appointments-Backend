<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsMonthlyTrendService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsMonthlyTrendGetController extends ResourceController
{
    private MetricsMonthlyTrendService $metricsMonthlyTrendService;

    public function __construct()
    {
        $this->metricsMonthlyTrendService = new MetricsMonthlyTrendService();
    }

    public function get(): ResponseInterface
    {
        // Nota: este endpoint no acepta dateFrom/dateTo (ventana fija de 6 meses).
        $request = new MetricsFilterRequest(
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $responses = $this->metricsMonthlyTrendService->get($request);

        return $this->response->setJSON($responses);
    }
}
