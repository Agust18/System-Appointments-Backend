<?php
namespace App\Converter\DayOff\Projection;

use App\Entity\DayOff\Projection\AffectedAppointmentProjection;

final class PrimitiveToAffectedAppointmentProjectionConverter
{
    public function convert(object $primitive): AffectedAppointmentProjection
    {
        return new AffectedAppointmentProjection(
            (int) $primitive->id,
            $primitive->fecha,
            substr($primitive->hora, 0, 5),
            $primitive->paciente_nombre,
            (int) $primitive->medical_id,
            $primitive->medico_nombre
        );
    }
}
