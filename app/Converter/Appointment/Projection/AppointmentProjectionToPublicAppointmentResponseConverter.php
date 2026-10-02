<?php

namespace App\Converter\Appointment\Projection;

use App\Dto\Response\Appointment\PublicAppointmentResponse;
use App\Entity\Appointment\Projection\AppointmentProjection;

final class AppointmentProjectionToPublicAppointmentResponseConverter
{
    public function convert(AppointmentProjection $appointment): PublicAppointmentResponse
    {
        return new PublicAppointmentResponse(
            $appointment->getId(),
            $appointment->getFecha()->format('Y-m-d'),
            $appointment->getHora()->format('H:i'),
            $appointment->getMedicoNombre(),
            $appointment->getServicioNombre(),
            $appointment->getStatus()
        );
    }
}
