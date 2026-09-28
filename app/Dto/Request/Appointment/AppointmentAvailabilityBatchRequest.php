<?php

namespace App\Dto\Request\Appointment;

final readonly class AppointmentAvailabilityBatchRequest
{
    public function __construct(
        private int $medicalId,
        private int $serviceId,
        private string $from,
        private string $to
    ) {}

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }
}
