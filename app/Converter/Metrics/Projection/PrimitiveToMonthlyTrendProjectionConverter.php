<?php

namespace App\Converter\Metrics\Projection;

use App\Entity\Metrics\Projection\MonthlyTrendProjection;

final class PrimitiveToMonthlyTrendProjectionConverter
{
    public function convert(object $primitive): MonthlyTrendProjection
    {
        return new MonthlyTrendProjection(
            month: $primitive->month,
            total: (int) $primitive->total,
            completed: (int) $primitive->completed,
            cancelled: (int) $primitive->cancelled,
            noshow: (int) $primitive->noshow
        );
    }
}
