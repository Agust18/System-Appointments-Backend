<?php

namespace App\Dto\Response\Metrics;

final readonly class MetricsTopServiceResponse
{
    public function __construct(
        public int $serviceId,
        public string $serviceName,
        public int $total
    ) {}
}
