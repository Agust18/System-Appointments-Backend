<?php

namespace App\Dto\Request\Appointment;

final readonly class AppointmentAvailabilityBatchRequest
{
    public function __construct(
        private int $medicalId,
        private int $serviceId,
        private string $from,
        private string $to,
        private mixed $excludeAppointmentId = null
    ) {}

    /** Valor tal como llegó (sin validar); lo valida AppointmentAvailabilityValidatorService. */
    public function getRawExcludeAppointmentId(): mixed
    {
        return $this->excludeAppointmentId;
    }

    /** Solo usar después de validar. */
    public function getExcludeAppointmentId(): ?int
    {
        return $this->excludeAppointmentId === null ? null : (int) $this->excludeAppointmentId;
    }

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
