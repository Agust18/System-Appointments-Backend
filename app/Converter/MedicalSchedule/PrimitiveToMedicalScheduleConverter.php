<?php
namespace App\converter\MedicalSchedule;

use App\Entity\MedicalSchedule\MedicalSchedule;
use DateTime;

final class PrimitiveToMedicalScheduleConverter
{
    public function convert(object $primitive): MedicalSchedule
    {
        return new MedicalSchedule(
            $primitive->id,
            $primitive->medical_id,
            $primitive->day_of_week,
            $primitive->start_time,
            $primitive->end_time,
            $primitive->is_active, 
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}