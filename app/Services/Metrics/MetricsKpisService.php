<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsKpisService
{
    private MetricsModel $metricsModel;
    private MetricsDateRangeResolverService $dateRangeResolverService;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->dateRangeResolverService = new MetricsDateRangeResolverService();
    }

    /**
     * @return array{total: int, pending: int, confirmed: int, cancelled: int, completed: int, noshow: int, revenue: float, noshowRate: float, cancellationRate: float}
     */
    public function get(MetricsFilterRequest $request): array
    {
        [$resolvedDateFrom, $resolvedDateTo] = $this->dateRangeResolverService->resolve(
            $request->getDateFrom(),
            $request->getDateTo()
        );

        $row = $this->metricsModel->getKpis($resolvedDateFrom, $resolvedDateTo, $request->getMedicalId());

        return $this->buildKpis($row);
    }

    private function buildKpis(object $row): array
    {
        $total = (int) $row->total;
        $cancelled = (int) $row->cancelled;
        $completed = (int) $row->completed;
        $noshow = (int) $row->noshow;

        return [
            'total' => $total,
            'pending' => (int) $row->pending,
            'confirmed' => (int) $row->confirmed,
            'cancelled' => $cancelled,
            'completed' => $completed,
            'noshow' => $noshow,
            'revenue' => (float) ($row->revenue ?? 0),
            'noshowRate' => $this->percentage($noshow, $completed + $noshow),
            'cancellationRate' => $this->percentage($cancelled, $total),
        ];
    }

    private function percentage(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round($numerator / $denominator * 100, 1);
    }
}
