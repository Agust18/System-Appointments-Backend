<?php

namespace App\Services\Metrics;

use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Models\Metrics\MetricsModel;

final class MetricsMonthlyTrendService
{
    private const WINDOW_MONTHS = 6;

    private MetricsModel $metricsModel;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
    }

    /**
     * @return array<int, array{month: string, total: int, completed: int, cancelled: int, noshow: int}>
     */
    public function get(MetricsFilterRequest $request): array
    {
        $months = $this->buildMonthsWindow();
        $dateFrom = reset($months) . '-01';
        $dateTo = date('Y-m-d');

        $rows = $this->metricsModel->getMonthlyTrend($request->getMedicalId(), $dateFrom, $dateTo);

        return $this->fillGaps($months, $rows);
    }

    /**
     * @return string[] los últimos 6 meses (formato Y-m) en orden cronológico ascendente
     */
    private function buildMonthsWindow(): array
    {
        $months = [];

        for ($i = self::WINDOW_MONTHS - 1; $i >= 0; $i--) {
            $months[] = date('Y-m', strtotime("-{$i} months"));
        }

        return $months;
    }

    /**
     * Rellena con 0 los meses de la ventana que no vinieron en el resultado de la query.
     *
     * @param string[] $months
     * @param object[] $rows
     * @return array<int, array{month: string, total: int, completed: int, cancelled: int, noshow: int}>
     */
    private function fillGaps(array $months, array $rows): array
    {
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row->month] = [
                'month' => $row->month,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'cancelled' => (int) $row->cancelled,
                'noshow' => (int) $row->noshow,
            ];
        }

        return array_map(
            fn (string $month) => $byMonth[$month] ?? [
                'month' => $month,
                'total' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'noshow' => 0,
            ],
            $months
        );
    }
}
