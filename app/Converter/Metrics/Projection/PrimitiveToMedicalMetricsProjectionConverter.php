<?php

namespace App\Converter\Metrics\Projection;

use App\Entity\Metrics\Projection\MedicalMetricsProjection;

final class PrimitiveToMedicalMetricsProjectionConverter
{
    public function convert(object $primitive): MedicalMetricsProjection
    {
        return new MedicalMetricsProjection(
            medicalId: (int) $primitive->medical_id,
            medicalName: $primitive->medical_name,
            total: (int) $primitive->total,
            completed: (int) $primitive->completed,
            cancelled: (int) $primitive->cancelled
        );
    }
}
