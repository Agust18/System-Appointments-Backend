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
}
