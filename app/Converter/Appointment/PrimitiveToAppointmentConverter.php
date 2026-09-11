<?php

namespace App\Converter\Appointment;

use App\Entity\Appointment\Appointment;
use DateTime;

final class PrimitiveToAppointmentConverter
{
    public function convert(object $primitive): Appointment
    {
        return new Appointment(
            $primitive->id ? (int) $primitive->id : null,
            (int) $primitive->medical_id,
            (int) $primitive->patient_id,
            (int) $primitive->service_id,
            new DateTime($primitive->fecha),
            new DateTime($primitive->hora),
            $primitive->hora_fin !== null ? new DateTime($primitive->hora_fin) : null,
            $primitive->nota,
            $primitive->status,
            new DateTime($primitive->created_at),
            $primitive->updated_at !== null ? new DateTime($primitive->updated_at) : null, 
        );
        
    }
}
