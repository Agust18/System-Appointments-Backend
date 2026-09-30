<?php

namespace App\Entity\Metrics\Projection;

final class MetricsKpisProjection
{
    public function __construct(
        private int $total,
        private int $pending,
        private int $confirmed,
        private int $cancelled,
        private int $completed,
        private int $noshow,
        private float $revenue
    ) {}

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPending(): int
    {
        return $this->pending;
    }

    public function getConfirmed(): int
    {
        return $this->confirmed;
    }

    public function getCancelled(): int
    {
        return $this->cancelled;
    }

    public function getCompleted(): int
    {
        return $this->completed;
    }

    public function getNoshow(): int
    {
        return $this->noshow;
    }

    public function getRevenue(): float
    {
        return $this->revenue;
    }

    public function getNoshowRate(): float
    {
        return $this->percentage($this->noshow, $this->completed + $this->noshow);
    }

    public function getCancellationRate(): float
    {
        return $this->percentage($this->cancelled, $this->total);
    }

    private function percentage(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round($numerator / $denominator * 100, 1);
    }
}
