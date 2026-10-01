<?php

namespace App\Converter\Patient\Projection;

use App\Entity\Patient\Projection\PatientProjection;
use DateTime;

final class PrimitiveToPatientProjectionConverter
{
    public function convert(object $primitive): PatientProjection
    {
        return new PatientProjection(
            id: $primitive->id,
            userId: $primitive->user_id,
            pacienteNombre: $primitive->paciente_nombre,
            phone: $primitive->phone ?? null,
            dni: $primitive->dni ?? null,
            email: $primitive->email ?? null,
            birthDate: new DateTime($primitive->birth_date),
            isActive: (bool)($primitive->is_active ?? true),
            createdAt: new DateTime($primitive->created_at),
            updatedAt: new DateTime($primitive->updated_at)
        );
    }
}
