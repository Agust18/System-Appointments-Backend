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
            
            // Projections (nombres)
            medicoNombre: $appointment->getMedicoNombre(),
            especialidad: $appointment->getEspecialidad(),
            pacienteNombre: $appointment->getPacienteNombre(),
            pacienteTelefono: $appointment->getPacienteTelefono(),
            pacienteDni: $appointment->getPacienteDni(),
            
            // Datos del turno
            fecha: $appointment->getFecha()->format('Y-m-d'),
            hora: $appointment->getHora()->format('H:i:s'),
            nota: $appointment->getNota(),
            status: $appointment->getStatus(),
            createdAt: $appointment->getCreatedAt()?->format('Y-m-d H:i:s'),
            updatedAt: $appointment->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}