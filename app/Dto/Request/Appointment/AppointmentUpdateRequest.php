<?php
namespace App\Dto\Request\Appointment;

use DateTime;

final readonly class AppointmentUpdateRequest
{
    public function __construct(
        private ?int $medicalId,
        private ?int $patientId,
        private int $serviceId,
        private ?string $fecha,
        private ?string $hora,
        private ?string $nota,
        private ?string $status
    ) {
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }
    public function getPatientId(): int
    {
        return $this->patientId;
    }
    public function getServiceId(): int
    {
        return $this->serviceId;
    }
    public function getFecha(): ?String
    {
        return $this->fecha;
    }
    public function getHora(): string
    {
        return $this->hora;
    }
    public function getNota(): string
    {
        return $this->nota;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
}