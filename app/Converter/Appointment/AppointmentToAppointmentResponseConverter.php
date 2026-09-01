<?php
namespace App\Converter\Appointment;

use App\Dto\Response\Appointment\AppointmentResponse;
use App\Entity\Appointment\Appointment;

final class AppointmentToAppointmentResponseConverter
{
    public function convert(Appointment $appointment): AppointmentResponse
    {
        return new AppointmentResponse(
            $appointment->getId(),
            $appointment->getMedicalId(),
            $appointment->getPatientId(),
            $appointment->getFecha()->format('Y-m-d'),
            $appointment->getHora()->format('H:i:s'),
            $appointment->getNota(),
            $appointment->getStatus(),
            $appointment->getCreatedAt()?->format('Y-m-d H:i:s'),
            $appointment->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}