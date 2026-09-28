<?php

namespace App\Converter\Metrics\Projection;

use App\Entity\Metrics\Projection\ScheduleHeatmapProjection;

final class PrimitiveToScheduleHeatmapProjectionConverter
{
    public function convert(object $primitive): ScheduleHeatmapProjection
    {
        return new ScheduleHeatmapProjection(
            dayOfWeek: (int) $primitive->day_of_week,
            hour: (int) $primitive->hour,
            total: (int) $primitive->total
        );
    }
}
