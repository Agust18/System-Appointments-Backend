<?php
namespace App\Converter\MedicalSchedule\Projection;

use App\Dto\Response\MedicalSchedule\Projection\MedicalScheduleProjectionResponse;
use App\Entity\MedicalSchedule\Projection\MedicalScheduleProjection;

final class MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter
{
    public function convert(MedicalScheduleProjection $schedule): MedicalScheduleProjectionResponse
    {
        return new MedicalScheduleProjectionResponse(
            $schedule->getId(),
            $schedule->getMedicalId(),
            $schedule->getMedicoNombre(),  
            $schedule->getDayOfWeek(),
            $schedule->getStartTime(),
            $schedule->getEndTime(),
            $schedule->isActive(),
            $schedule->getCreatedAt()?->format('Y-m-d H:i:s'),
            $schedule->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}