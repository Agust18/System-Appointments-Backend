<?php

namespace App\Dto\Response\Metrics;

final readonly class MetricsMonthlyTrendResponse
{
    public function __construct(
        public string $month,
        public int $total,
        public int $completed,
        public int $cancelled,
        public int $noshow
    ) {}
}
