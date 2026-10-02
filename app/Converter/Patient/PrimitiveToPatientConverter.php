<?php
namespace App\Converter\Patient;
use App\Entity\Patient\Patient;
use DateTime;

final class PrimitiveToPatientConverter
{
    public function convert(object $primitive): Patient
    {
        return new Patient(
            $primitive->id,
            $primitive->user_id,
            $primitive->birth_date ? new DateTime($primitive->birth_date) : null,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}