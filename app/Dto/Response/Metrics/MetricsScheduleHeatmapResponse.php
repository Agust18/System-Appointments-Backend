<?php

namespace App\Dto\Response\Metrics;

final readonly class MetricsScheduleHeatmapResponse
{
    public function __construct(
        public int $dayOfWeek,
        public int $hour,
        public int $total
    ) {}
}
