<?php

namespace App\Converter\Metrics\Projection;

use App\Dto\Response\Metrics\MetricsMonthlyTrendResponse;
use App\Entity\Metrics\Projection\MonthlyTrendProjection;

final class MonthlyTrendProjectionToMetricsMonthlyTrendResponseConverter
{
    public function convert(MonthlyTrendProjection $projection): MetricsMonthlyTrendResponse
    {
        return new MetricsMonthlyTrendResponse(
            month: $projection->getMonth(),
            total: $projection->getTotal(),
            completed: $projection->getCompleted(),
            cancelled: $projection->getCancelled(),
            noshow: $projection->getNoshow()
        );
    }
}
