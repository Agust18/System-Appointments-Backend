<?php

namespace App\Converter\Metrics\Projection;

use App\Dto\Response\Metrics\MetricsByMedicalResponse;
use App\Entity\Metrics\Projection\MedicalMetricsProjection;

final class MedicalMetricsProjectionToMetricsByMedicalResponseConverter
{
    public function convert(MedicalMetricsProjection $projection): MetricsByMedicalResponse
    {
        return new MetricsByMedicalResponse(
            medicalId: $projection->getMedicalId(),
            medicalName: $projection->getMedicalName(),
            total: $projection->getTotal(),
            completed: $projection->getCompleted(),
            cancelled: $projection->getCancelled()
        );
    }
}
