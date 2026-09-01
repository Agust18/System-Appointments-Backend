<?php
namespace App\Dto\Request\Appointment;

final readonly class AppointmentRequest
{
    public function __construct(
        public int $medicalId,
        public int $patientId,
        public ?int $serviceId,
        public string $fecha,
        public string $hora,
        public ?string $nota,
        public string $status = 'pending'
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
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }
    public function getFecha(): string
    {
        return $this->fecha;
    }
    public function getHora(): string
    {
        return $this->hora;
    }
    public function getNota(): ?string
    {
        return $this->nota;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
}