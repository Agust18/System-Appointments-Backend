<?php

namespace App\Entity\Metrics\Projection;

final class MedicalMetricsProjection
{
    public function __construct(
        private int $medicalId,
        private string $medicalName,
        private int $total,
        private int $completed,
        private int $cancelled
    ) {}

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getMedicalName(): string
    {
        return $this->medicalName;
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
}
