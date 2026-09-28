<?php

namespace App\Converter\Appointment\Projection;

use App\Dto\Response\Appointment\Projection\AppointmentProjectionResponse;
use App\Entity\Appointment\Projection\AppointmentProjection;

final class AppointmentProjectionToAppointmentProjectionResponseConverter
{
    public function convert(AppointmentProjection $appointment): AppointmentProjectionResponse
    {
        return new AppointmentProjectionResponse(
            // IDs
            id: $appointment->getId(),
            medicalId: $appointment->getMedicalId(),
            patientId: $appointment->getPatientId(),
            serviceId: $appointment->getServiceId(),


            // Projections (nombres)
            medicoNombre: $appointment->getMedicoNombre(),
            pacienteNombre: $appointment->getPacienteNombre(),
            servicioNombre: $appointment->getServicioNombre(),
            paciente_email:$appointment->getEmail(),

            fecha: $appointment->getFecha()->format('Y-m-d'),
            hora: $appointment->getHora()->format('H:i:s'),
            nota: $appointment->getNota(),
            status: $appointment->getStatus(),

        );
    }
}
