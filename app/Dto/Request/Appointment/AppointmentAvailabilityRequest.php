<?php
namespace App\Dto\Request\Appointment;

use DateTime;

final readonly class AppointmentAvailabilityRequest
{
    public function __construct(
        private int $medicalId,
        private int $serviceId,
        private string $fecha
    ) {
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }
}
