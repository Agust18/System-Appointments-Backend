<?php
namespace App\Dto\Request\Appointment;

final readonly class AppointmentAvailabilityRequest
{
    public function __construct(
        public int $medicalId,
        public int $serviceId,
        public string $fecha
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
