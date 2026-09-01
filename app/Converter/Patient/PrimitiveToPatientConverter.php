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
            new DateTime($primitive->birth_date),         
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}