<?php
namespace App\Dto\Request\Appointment;

final readonly class AppointmentUpdateRequest
{
    public function __construct(
        private ?int $medicalId = null,
        private ?int $patientId = null,
        private ?int $serviceId = null,
        private ?string $fecha = null,
        private ?string $hora = null,
        private ?string $nota = null,
        private ?string $status = null
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