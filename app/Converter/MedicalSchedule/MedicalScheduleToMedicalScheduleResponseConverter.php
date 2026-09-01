<?php
namespace App\Converter\MedicalSchedule;

use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Entity\MedicalSchedule\MedicalSchedule;

final class MedicalScheduleToMedicalScheduleResponseConverter
{
    public function convert(MedicalSchedule $schedule): MedicalScheduleResponse
    {
        return new MedicalScheduleResponse(
            $schedule->getId(),
            $schedule->getMedicalId(),
            $schedule->getDayOfWeek(),
            $schedule->getStartTime(),
            $schedule->getEndTime(),
            $schedule->isActive(),
            $schedule->getCreatedAt()?->format('Y-m-d H:i:s'),
            $schedule->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}