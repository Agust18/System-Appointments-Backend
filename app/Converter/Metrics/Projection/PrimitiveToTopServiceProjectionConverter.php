<?php

namespace App\Converter\Metrics\Projection;

use App\Entity\Metrics\Projection\TopServiceProjection;

final class PrimitiveToTopServiceProjectionConverter
{
    public function convert(object $primitive): TopServiceProjection
    {
        return new TopServiceProjection(
            serviceId: (int) $primitive->service_id,
            serviceName: $primitive->service_name,
            total: (int) $primitive->total
        );
    }
}
