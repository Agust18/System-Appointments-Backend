<?php
namespace App\Converter\Patient;
use App\Entity\Patient\Patient;
use DateTime;

final class PrimitiveToPatientConverter
{
    public function convert(object $primitive): Patient
    {
        return new Patient(
            (int) $primitive->id,
            $primitive->first_name,
            $primitive->last_name,
            $primitive->email,
            (int) $primitive->dni,
            $primitive->phone,
            $primitive->birth_date ? new DateTime($primitive->birth_date) : null,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
