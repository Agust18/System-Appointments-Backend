<?php

namespace App\Entity\Metrics\Projection;

final class TopServiceProjection
{
    public function __construct(
        private int $serviceId,
        private string $serviceName,
        private int $total
    ) {}

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
