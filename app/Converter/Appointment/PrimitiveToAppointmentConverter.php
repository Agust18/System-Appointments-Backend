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
            $primitive->hora_fin,
            $primitive->nota ?? null,
            $primitive->status,
            $primitive->created_at,
            $primitive->updated_at
        );
    }
}
