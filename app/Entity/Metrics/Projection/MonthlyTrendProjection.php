<?php

namespace App\Entity\Metrics\Projection;

final class MonthlyTrendProjection
{
    public function __construct(
        private string $month,
        private int $total,
        private int $completed,
        private int $cancelled,
        private int $noshow
    ) {}

    public function getMonth(): string
    {
        return $this->month;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getCompleted(): int
    {
        return $this->completed;
    }

    public function getCancelled(): int
    {
        return $this->cancelled;
    }

    public function getNoshow(): int
    {
        return $this->noshow;
    }
}
