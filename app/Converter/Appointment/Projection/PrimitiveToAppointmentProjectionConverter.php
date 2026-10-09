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
            serviceId: (int) $primitive->service_id,


            paciente_email:$primitive->paciente_email,
            medicoNombre: $primitive->medico_nombre,
            servicioNombre: $primitive->servicio_nombre ?? null,
            pacienteNombre: $primitive->paciente_nombre,


            fecha: new DateTime($primitive->fecha),
            hora: new DateTime($primitive->hora),
            nota: $primitive->nota ?? null,
            status: $primitive->status,
            paciente_telefono: $primitive->paciente_telefono ?? null,

        );
    }
}
