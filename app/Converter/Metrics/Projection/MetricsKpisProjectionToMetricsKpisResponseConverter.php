<?php

namespace App\Converter\Metrics\Projection;

use App\Dto\Response\Metrics\MetricsKpisResponse;
use App\Entity\Metrics\Projection\MetricsKpisProjection;

final class MetricsKpisProjectionToMetricsKpisResponseConverter
{
    public function convert(MetricsKpisProjection $projection): MetricsKpisResponse
    {
        return new MetricsKpisResponse(
            total: $projection->getTotal(),
            pending: $projection->getPending(),
            confirmed: $projection->getConfirmed(),
            cancelled: $projection->getCancelled(),
            completed: $projection->getCompleted(),
            noshow: $projection->getNoshow(),
            revenue: $projection->getRevenue(),
            noshowRate: $projection->getNoshowRate(),
            cancellationRate: $projection->getCancellationRate()
        );
    }
}
