<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsScheduleHeatmapService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array<int, array{dayOfWeek: int, hour: int, total: int}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getScheduleHeatmap($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(
            fn (object $row) => [
                'dayOfWeek' => (int) $row->day_of_week,
                'hour' => (int) $row->hour,
                'total' => (int) $row->total,
            ],
            $rows
        );
    }
}
