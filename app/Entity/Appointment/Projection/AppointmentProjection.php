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

        //(projections)
        private string $medicoNombre,
        private string $especialidad,
        private string $pacienteNombre,
        private ?string $pacienteTelefono,
        private ?string $pacienteDni,

        // Datos del turno
        private DateTime $fecha,
        private DateTime $hora,
        private ?string $nota,
        private string $status,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

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
    public function getEspecialidad(): string
    {
        return $this->especialidad;
    }
    public function getPacienteNombre(): string
    {
        return $this->pacienteNombre;
    }
    public function getPacienteTelefono(): ?string
    {
        return $this->pacienteTelefono;
    }
    public function getPacienteDni(): ?string
    {
        return $this->pacienteDni;
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
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}