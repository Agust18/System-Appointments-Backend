<?php
namespace App\Converter\Appointment;

use App\Dto\Response\Appointment\AppointmentAvailabilityBatchResponse;

final class SlotsByDateToAvailabilityBatchResponseConverter
{
    /**
     * @param array<string, string[]> $slotsByDate
     */
    public function convert(array $slotsByDate): AppointmentAvailabilityBatchResponse
    {
        return new AppointmentAvailabilityBatchResponse($slotsByDate);
    }
}
