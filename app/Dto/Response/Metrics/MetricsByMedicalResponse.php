<?php

namespace App\Dto\Response\Metrics;

final readonly class MetricsByMedicalResponse
{
    public function __construct(
        public int $medicalId,
        public string $medicalName,
        public int $total,
        public int $completed,
        public int $cancelled
    ) {}
}
