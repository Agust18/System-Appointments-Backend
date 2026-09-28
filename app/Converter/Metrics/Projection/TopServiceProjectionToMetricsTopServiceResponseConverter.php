<?php

namespace App\Converter\Metrics\Projection;

use App\Dto\Response\Metrics\MetricsTopServiceResponse;
use App\Entity\Metrics\Projection\TopServiceProjection;

final class TopServiceProjectionToMetricsTopServiceResponseConverter
{
    public function convert(TopServiceProjection $projection): MetricsTopServiceResponse
    {
        return new MetricsTopServiceResponse(
            serviceId: $projection->getServiceId(),
            serviceName: $projection->getServiceName(),
            total: $projection->getTotal()
        );
    }
}
