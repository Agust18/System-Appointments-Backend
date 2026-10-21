<?php
namespace App\Converter\DayOff\Projection;

use App\Dto\Response\DayOff\AffectedAppointmentResponse;
use App\Entity\DayOff\Projection\AffectedAppointmentProjection;

final class AffectedAppointmentProjectionToAffectedAppointmentResponseConverter
{
    public function convert(AffectedAppointmentProjection $projection): AffectedAppointmentResponse
    {
        return new AffectedAppointmentResponse(
            $projection->getId(),
            $projection->getFecha(),
            $projection->getHora(),
            $projection->getPacienteNombre(),
            $projection->getMedicalId(),
            $projection->getMedicoNombre()
        );
    }
}
