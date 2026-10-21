<?php
namespace App\Converter\DayOff;

use App\Dto\Response\DayOff\DayOffResponse;
use App\Entity\DayOff\DayOff;

final class DayOffToDayOffResponseConverter
{
    public function convert(DayOff $dayOff): DayOffResponse
    {
        return new DayOffResponse(
            $dayOff->getId(),
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo(),
            $dayOff->getReason(),
            $dayOff->getCreatedBy(),
            $dayOff->getCreatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
