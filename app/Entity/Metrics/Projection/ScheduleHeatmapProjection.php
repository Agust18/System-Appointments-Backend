<?php

namespace App\Entity\Metrics\Projection;

final class ScheduleHeatmapProjection
{
    public function __construct(
        private int $dayOfWeek,
        private int $hour,
        private int $total
    ) {}

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
