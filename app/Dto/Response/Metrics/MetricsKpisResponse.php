<?php

namespace App\Dto\Response\Metrics;

final readonly class MetricsKpisResponse
{
    public function __construct(
        public int $total,
        public int $pending,
        public int $confirmed,
        public int $cancelled,
        public int $completed,
        public int $noshow,
        public float $revenue,
        public float $noshowRate,
        public float $cancellationRate
    ) {}
}
