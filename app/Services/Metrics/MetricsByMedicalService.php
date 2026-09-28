<?php

namespace App\Services\Metrics;

use App\Converter\Metrics\Projection\MedicalMetricsProjectionToMetricsByMedicalResponseConverter;
use App\Converter\Metrics\Projection\PrimitiveToMedicalMetricsProjectionConverter;
use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsByMedicalService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;
    private PrimitiveToMedicalMetricsProjectionConverter $projectionConverter;
    private MedicalMetricsProjectionToMetricsByMedicalResponseConverter $responseConverter;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
        $this->projectionConverter = new PrimitiveToMedicalMetricsProjectionConverter();
        $this->responseConverter = new MedicalMetricsProjectionToMetricsByMedicalResponseConverter();
    }

    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getByMedical($resolvedDateFrom, $resolvedDateTo);

        return array_map(
            fn (object $row) => $this->responseConverter->convert($this->projectionConverter->convert($row)),
            $rows
        );
    }
}
