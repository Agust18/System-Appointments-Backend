<?php
namespace App\Converter\DayOff;

use App\Dto\Response\DayOff\AffectedAppointmentResponse;
use App\Dto\Response\DayOff\DayOffSavedResponse;
use App\Entity\DayOff\DayOff;

final class DayOffToDayOffSavedResponseConverter
{
    /**
     * @param AffectedAppointmentResponse[] $affectedAppointments
     */
    public function convert(DayOff $dayOff, array $affectedAppointments): DayOffSavedResponse
    {
        return new DayOffSavedResponse(
            $dayOff->getId(),
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo(),
            $dayOff->getReason(),
            $dayOff->getCreatedBy(),
            $dayOff->getCreatedAt()?->format('Y-m-d H:i:s'),
            $affectedAppointments
        );
    }
}
