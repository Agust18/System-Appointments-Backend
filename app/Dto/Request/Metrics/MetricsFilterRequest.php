<?php

namespace App\Dto\Request\Metrics;

final readonly class MetricsFilterRequest
{
    public function __construct(
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        private ?int $medicalId = null
    ) {}

    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function hasDateFrom(): bool
    {
        return !empty($this->dateFrom);
    }

    public function hasDateTo(): bool
    {
        return !empty($this->dateTo);
    }

    public function hasMedicalId(): bool
    {
        return !empty($this->medicalId);
    }
}
