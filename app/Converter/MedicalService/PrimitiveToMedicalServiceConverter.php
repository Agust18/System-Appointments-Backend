<?php
namespace App\Converter\MedicalService;

use App\Entity\MedicalService\MedicalService;
use DateTime;

final class PrimitiveToMedicalServiceConverter
{
    public function convert(object $primitive): MedicalService
    {
        return new MedicalService(
            $primitive->id,
            $primitive->medical_id,
            $primitive->service_id,
            $primitive->custom_price !== null ? (float)$primitive->custom_price : null,
            $primitive->custom_duration_minutes !== null ? (int)$primitive->custom_duration_minutes : null,
            $primitive->is_active,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}