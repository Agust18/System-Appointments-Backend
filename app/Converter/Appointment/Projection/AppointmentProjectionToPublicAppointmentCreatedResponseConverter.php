<?php

namespace App\Converter\Appointment\Projection;

use App\Dto\Response\Appointment\PublicAppointmentCreatedResponse;
use App\Entity\Appointment\AppointmentAccess;
use App\Entity\Appointment\Projection\AppointmentProjection;

final class AppointmentProjectionToPublicAppointmentCreatedResponseConverter
{
    public function convert(AppointmentProjection $appointment, AppointmentAccess $access): PublicAppointmentCreatedResponse
    {
        return new PublicAppointmentCreatedResponse(
            $appointment->getId(),
            $appointment->getFecha()->format('Y-m-d'),
            $appointment->getHora()->format('H:i'),
            $appointment->getMedicoNombre(),
            $appointment->getServicioNombre(),
            $appointment->getStatus(),
            $access->code,
            $access->token
        );
    }
}
