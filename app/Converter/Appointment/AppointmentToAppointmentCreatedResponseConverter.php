<?php
namespace App\Converter\Appointment;

use App\Dto\Response\Appointment\AppointmentCreatedResponse;
use App\Entity\Appointment\Appointment;
use App\Entity\Appointment\AppointmentAccess;

final class AppointmentToAppointmentCreatedResponseConverter
{
    public function convert(Appointment $appointment, AppointmentAccess $access): AppointmentCreatedResponse
    {
        return new AppointmentCreatedResponse(
            $appointment->getId(),
            $appointment->getMedicalId(),
            $appointment->getPatientId(),
            $appointment->getFecha(),
            $appointment->getHora()->format('H:i:s'),
            $appointment->getNota(),
            $appointment->getStatus(),
            $appointment->getCreatedAt(),
            $appointment->getUpdatedAt(),
            $access->code
        );
    }
}
