<?php

namespace App\Services\Metrics;

use App\Converter\Metrics\Projection\MonthlyTrendProjectionToMetricsMonthlyTrendResponseConverter;
use App\Converter\Metrics\Projection\PrimitiveToMonthlyTrendProjectionConverter;
use App\Dto\Request\Metrics\MetricsFilterRequest;
use App\Dto\Response\Metrics\MetricsMonthlyTrendResponse;
use App\Entity\Metrics\Projection\MonthlyTrendProjection;
use App\Models\Metrics\MetricsModel;

final class MetricsMonthlyTrendService
{
    private const WINDOW_MONTHS = 6;

    private MetricsModel $metricsModel;
    private PrimitiveToMonthlyTrendProjectionConverter $projectionConverter;
    private MonthlyTrendProjectionToMetricsMonthlyTrendResponseConverter $responseConverter;

    public function __construct()
    {
        $this->metricsModel = new MetricsModel();
        $this->projectionConverter = new PrimitiveToMonthlyTrendProjectionConverter();
        $this->responseConverter = new MonthlyTrendProjectionToMetricsMonthlyTrendResponseConverter();
    }

    /**
     * @return MetricsMonthlyTrendResponse[]
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
     * @return MetricsMonthlyTrendResponse[]
     */
    private function fillGaps(array $months, array $rows): array
    {
        $byMonth = [];
        foreach ($rows as $row) {
            $projection = $this->projectionConverter->convert($row);
            $byMonth[$projection->getMonth()] = $projection;
        }

        return array_map(
            fn (string $month) => $this->responseConverter->convert(
                $byMonth[$month] ?? new MonthlyTrendProjection($month, 0, 0, 0, 0)
            ),
            $months
        );
    }
}
