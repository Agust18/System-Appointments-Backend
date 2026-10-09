<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsTopServicesService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array<int, array{serviceId: int, serviceName: string, total: int}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getTopServices($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(
            fn (object $row) => [
                'serviceId' => (int) $row->service_id,
                'serviceName' => $row->service_name,
                'total' => (int) $row->total,
            ],
            $rows
        );
    }
}
