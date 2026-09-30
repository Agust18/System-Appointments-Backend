<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsByMedicalService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array<int, array{medicalId: int, medicalName: string, total: int, completed: int, cancelled: int}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getByMedical($resolvedDateFrom, $resolvedDateTo);

        return array_map(
            fn (object $row) => [
                'medicalId' => (int) $row->medical_id,
                'medicalName' => $row->medical_name,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'cancelled' => (int) $row->cancelled,
            ],
            $rows
        );
    }
}
