<?php

namespace App\Converter\Appointment;

use App\Entity\Appointment\Appointment;
use DateTime;

final class PrimitiveToAppointmentConverter
{
    public function convert(object $primitive): Appointment
    {
        return new Appointment(
            $primitive->id ? (int)$primitive->id : null,
            $primitive->medical_id,
            $primitive->patient_id,
            $primitive->service_id,
            new DateTime($primitive->fecha),
            new DateTime($primitive->hora),
            new DateTime($primitive->hora_fin),
            $primitive->nota,
            $primitive->status,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
