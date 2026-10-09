<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsTopSpecialtiesService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array<int, array{specialtyId: int, specialtyName: string, total: int}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $rows = $this->metricsModel->getTopSpecialties($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return array_map(fn (object $row) => $this->buildItem($row), $rows);
    }

    private function buildItem(object $row): array
    {
        return [
            'specialtyId' => (int) $row->specialty_id,
            'specialtyName' => $row->specialty_name,
            'total' => (int) $row->total,
        ];
    }
}
