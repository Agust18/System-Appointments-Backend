<?php

namespace App\Converter\Patient\Projection;

use App\Entity\Patient\Projection\PatientProjection;
use DateTime;

final class PrimitiveToPatientProjectionConverter
{
    public function convert(object $primitive): PatientProjection
    {
        return new PatientProjection(
            $primitive->id,
            $primitive->user_id,
            $primitive->paciente_nombre,
            $primitive->dni ?? null,
            $primitive->email ?? null,
            $primitive->phone ?? null,
            new DateTime($primitive->birth_date),
            (bool)($primitive->is_active ?? true),
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
