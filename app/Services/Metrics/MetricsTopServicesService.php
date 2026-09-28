<?php

namespace App\Services\Metrics;

use App\Converter\Metrics\Projection\PrimitiveToTopServiceProjectionConverter;
use App\Converter\Metrics\Projection\TopServiceProjectionToMetricsTopServiceResponseConverter;
use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsTopServicesService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;
    private PrimitiveToTopServiceProjectionConverter $projectionConverter;
    private TopServiceProjectionToMetricsTopServiceResponseConverter $responseConverter;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
        $this->projectionConverter = new PrimitiveToTopServiceProjectionConverter();
        $this->responseConverter = new TopServiceProjectionToMetricsTopServiceResponseConverter();
    }

    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getTopServices($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(
            fn (object $row) => $this->responseConverter->convert($this->projectionConverter->convert($row)),
            $rows
        );
    }
}
