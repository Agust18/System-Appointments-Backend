<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsByMedicalService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsByMedicalGetController extends ResourceController
{
    private MetricsByMedicalService $metricsByMedicalService;

    public function __construct()
    {
        $this->metricsByMedicalService = new MetricsByMedicalService();
    }

    public function get(): ResponseInterface
    {
        // Nota: este endpoint no acepta medicalId (el gráfico ES por médico).
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null
        );

        $responses = $this->metricsByMedicalService->get($request);

        return $this->response->setJSON($responses);
    }
}
