<?php
namespace App\Converter\DayOff;

use App\Entity\DayOff\DayOff;
use DateTime;

final class PrimitiveToDayOffConverter
{
    public function convert(object $primitive): DayOff
    {
        return new DayOff(
            (int) $primitive->id,
            $primitive->medical_id !== null ? (int) $primitive->medical_id : null,
            $primitive->date_from,
            $primitive->date_to,
            $primitive->reason,
            (int) $primitive->created_by,
            new DateTime($primitive->created_at)
        );
    }
}
