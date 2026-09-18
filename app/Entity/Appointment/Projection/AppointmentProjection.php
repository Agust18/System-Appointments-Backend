<?php

namespace App\Entity\Appointment\Projection;

use DateTime;

final class AppointmentProjection
{
    public function __construct(
        // id (para operaciones)
        private int $id,
        private int $medicalId,
        private int $patientId,
        private int $serviceId,

        //(projections)
        private string $medicoNombre,
        private string $pacienteNombre,
        private string $servicioNombre,


        // Datos del turno
        private DateTime $fecha,
        private DateTime $hora,
        private ?string $nota,
        private string $status,

    ) {}

    // Getters para IDs
    public function getId(): int
    {
        return $this->id;
    }
    public function getMedicalId(): int
    {
        return $this->medicalId;
    }
    public function getPatientId(): int
    {
        return $this->patientId;
    }

    // Getters para nombres (projections)
    public function getMedicoNombre(): string
    {
        return $this->medicoNombre;
    }

    public function getPacienteNombre(): string
    {
        return $this->pacienteNombre;
    }


    // Getters para datos del turno
    public function getFecha(): DateTime
    {
        return $this->fecha;
    }
    public function getHora(): DateTime
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

    public function getServiceId(): int
    {
        return $this->serviceId;
    }
    public function getServicioNombre(): string
    {
        return $this->servicioNombre;
    }
}
