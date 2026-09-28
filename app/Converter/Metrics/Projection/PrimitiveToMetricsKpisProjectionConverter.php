<?php

namespace App\Converter\Metrics\Projection;

use App\Entity\Metrics\Projection\MetricsKpisProjection;

final class PrimitiveToMetricsKpisProjectionConverter
{
    public function convert(object $primitive): MetricsKpisProjection
    {
        return new MetricsKpisProjection(
            total: (int) $primitive->total,
            pending: (int) $primitive->pending,
            confirmed: (int) $primitive->confirmed,
            cancelled: (int) $primitive->cancelled,
            completed: (int) $primitive->completed,
            noshow: (int) $primitive->noshow,
            revenue: (float) ($primitive->revenue ?? 0)
        );
    }
}
