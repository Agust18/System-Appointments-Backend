<?php

namespace App\Services\Metrics;

use App\Converter\Metrics\Projection\PrimitiveToScheduleHeatmapProjectionConverter;
use App\Converter\Metrics\Projection\ScheduleHeatmapProjectionToMetricsScheduleHeatmapResponseConverter;
use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsScheduleHeatmapService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;
    private PrimitiveToScheduleHeatmapProjectionConverter $projectionConverter;
    private ScheduleHeatmapProjectionToMetricsScheduleHeatmapResponseConverter $responseConverter;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
        $this->projectionConverter = new PrimitiveToScheduleHeatmapProjectionConverter();
        $this->responseConverter = new ScheduleHeatmapProjectionToMetricsScheduleHeatmapResponseConverter();
    }

    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getScheduleHeatmap($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(
            fn (object $row) => $this->responseConverter->convert($this->projectionConverter->convert($row)),
            $rows
        );
    }
}
