<?php
namespace App\Converter\Medical\Projection;

use App\Entity\Medical\Projection\MedicalProjection;
use DateTime;

final class PrimitiveToMedicalProjectionConverter
{
    public function convert(object $primitive): MedicalProjection
    {
        return new MedicalProjection(
            $primitive->id,
            $primitive->user_id,
            $primitive->specialty_id,
            $primitive->medico_nombre,
            $primitive->email ?? null,
            $primitive->especialidad_nombre,
            $primitive->tuition,
            $primitive->phone ?? null,
            (bool)($primitive->is_active ?? true),
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}