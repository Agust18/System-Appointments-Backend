<?php

namespace App\Dto\Response\Appointment;

final readonly class AppointmentAvailabilityBatchResponse
{
    /**
     * @param array<string, string[]> $data fecha (Y-m-d) => array de start_time ("HH:MM")
     */
    public function __construct(
        public array $data
    ) {}
}
