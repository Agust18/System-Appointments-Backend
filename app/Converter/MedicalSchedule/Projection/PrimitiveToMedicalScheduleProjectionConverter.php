<?php
namespace App\Converter\MedicalSchedule\Projection;

use App\Entity\MedicalSchedule\Projection\MedicalScheduleProjection;
use DateTime;

final class PrimitiveToMedicalScheduleProjectionConverter
{
    public function convert(object $primitive): MedicalScheduleProjection
    {
        return new MedicalScheduleProjection(
            $primitive->id,
            $primitive->medical_id,
            $primitive->medico_nombre,
            $primitive->day_of_week,
            $primitive->start_time,
            $primitive->end_time,
            $primitive->is_active,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}