<?php
namespace App\Converter\Appointment\Projection;

use App\Entity\Appointment\Projection\AppointmentProjection;
use DateTime;

final class PrimitiveToAppointmentProjectionConverter
{
    public function convert(object $primitive): AppointmentProjection
    {
        return new AppointmentProjection(
            id: $primitive->id,
            medicalId: $primitive->medical_id,
            patientId: $primitive->patient_id,
            medicoNombre: $primitive->medico_nombre,
            especialidad: $primitive->especialidad,
            pacienteNombre: $primitive->paciente_nombre,
            pacienteTelefono: $primitive->paciente_telefono ?? null,
            pacienteDni: $primitive->paciente_dni ?? null,
            fecha: new DateTime($primitive->fecha),
            hora: new DateTime($primitive->hora),
            nota: $primitive->nota ?? null,
            status: $primitive->status,
            createdAt: isset($primitive->created_at) ? new DateTime($primitive->created_at) : null,
            updatedAt: isset($primitive->updated_at) ? new DateTime($primitive->updated_at) : null
        );
    }
}