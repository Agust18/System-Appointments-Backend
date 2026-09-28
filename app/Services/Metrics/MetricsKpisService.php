<?php

namespace App\Services\Metrics;

use App\Converter\Metrics\Projection\MetricsKpisProjectionToMetricsKpisResponseConverter;
use App\Converter\Metrics\Projection\PrimitiveToMetricsKpisProjectionConverter;
use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Dto\Response\Metrics\MetricsKpisResponse;
use App\Models\Metrics\MetricsModel;

final class MetricsKpisService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;
    private PrimitiveToMetricsKpisProjectionConverter $projectionConverter;
    private MetricsKpisProjectionToMetricsKpisResponseConverter $responseConverter;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
        $this->projectionConverter = new PrimitiveToMetricsKpisProjectionConverter();
        $this->responseConverter = new MetricsKpisProjectionToMetricsKpisResponseConverter();
    }

    public function get(MetricsFilterRequest $request): MetricsKpisResponse
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $primitive = $this->metricsModel->getKpis($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());
        $projection = $this->projectionConverter->convert($primitive);

        return $this->responseConverter->convert($projection);
    }
}
