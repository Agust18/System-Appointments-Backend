<?php

namespace App\Converter\Metrics\Projection;

use App\Dto\Response\Metrics\MetricsScheduleHeatmapResponse;
use App\Entity\Metrics\Projection\ScheduleHeatmapProjection;

final class ScheduleHeatmapProjectionToMetricsScheduleHeatmapResponseConverter
{
    public function convert(ScheduleHeatmapProjection $projection): MetricsScheduleHeatmapResponse
    {
        return new MetricsScheduleHeatmapResponse(
            dayOfWeek: $projection->getDayOfWeek(),
            hour: $projection->getHour(),
            total: $projection->getTotal()
        );
    }
}
