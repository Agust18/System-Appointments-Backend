<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsScheduleHeatmapService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsScheduleHeatmapGetController extends ResourceController
{
    private MetricsScheduleHeatmapService $metricsScheduleHeatmapService;

    public function __construct()
    {
        $this->metricsScheduleHeatmapService = new MetricsScheduleHeatmapService();
    }

    public function get(): ResponseInterface
    {
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null,
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $responses = $this->metricsScheduleHeatmapService->get($request);

        return $this->response->setJSON($responses);
    }
}
