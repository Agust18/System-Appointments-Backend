<?php

namespace App\Controllers\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Services\Metrics\MetricsTopSpecialtiesService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MetricsTopSpecialtiesGetController extends ResourceController
{
    private MetricsTopSpecialtiesService $metricsTopSpecialtiesService;

    public function __construct()
    {
        $this->metricsTopSpecialtiesService = new MetricsTopSpecialtiesService();
    }

    public function get(): ResponseInterface
    {
        $request = new MetricsFilterRequest(
            dateFrom: $this->request->getGet('dateFrom') ?: null,
            dateTo: $this->request->getGet('dateTo') ?: null,
            medicalId: $this->request->getGet('medicalId') ? (int) $this->request->getGet('medicalId') : null
        );

        $responses = $this->metricsTopSpecialtiesService->get($request);

        return $this->response->setJSON($responses);
    }
}
