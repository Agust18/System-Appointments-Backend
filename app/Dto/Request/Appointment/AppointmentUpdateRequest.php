<?php
namespace App\Dto\Request\Appointment;

final readonly class AppointmentUpdateRequest
{
    public function __construct(
        public ?int $medicalId = null,
        public ?int $patientId = null,
        public ?int $serviceId = null,
        public ?string $fecha = null,
        public ?string $hora = null,
        public ?string $nota = null,
        public ?string $status = null
    ) {
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }
    public function getPatientId(): ?int
    {
        return $this->patientId;
    }
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }
    public function getFecha(): ?string
    {
        return $this->fecha;
    }
    public function getHora(): ?string
    {
        return $this->hora;
    }
    public function getNota(): ?string
    {
        return $this->nota;
    }
    public function getStatus(): ?string
    {
        return $this->status;
    }
}