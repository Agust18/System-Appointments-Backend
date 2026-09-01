<?php
namespace App\Converter\MedicalService\Projection;

use App\Entity\MedicalService\Projection\MedicalServiceProjection;

final class PrimitiveToMedicalServiceProjectionConverter
{
    public function convert(object $primitive): MedicalServiceProjection
    {
        return new MedicalServiceProjection(
            $primitive->medical_service_id,
            $primitive->medical_id,
            $primitive->service_id,
            $primitive->name,
            $primitive->description ?? null,
            $primitive->base_duration_minutes,
            $primitive->buffer_minutes,
            $primitive->base_price,
            $primitive->slot_step_minutes,
            $primitive->custom_price !== null ? (float)$primitive->custom_price : null,
            $primitive->custom_duration_minutes !== null ? (int)$primitive->custom_duration_minutes : null,
            $primitive->effective_duration_minutes,
            $primitive->effective_price,
            (bool)$primitive->is_active
        );
    }
}
