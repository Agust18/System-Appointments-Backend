<?php
namespace App\Entity\DayOff\Projection;

/**
 * Turno pending/confirmed que cae dentro de un bloqueo. Solo lectura: nunca se persiste.
 */
final class AffectedAppointmentProjection
{
    public function __construct(
        private int $id,
        private string $fecha,
        private string $hora,
        private string $pacienteNombre,
        private int $medicalId,
        private string $medicoNombre
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function getHora(): string
    {
        return $this->hora;
    }

    public function getPacienteNombre(): string
    {
        return $this->pacienteNombre;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getMedicoNombre(): string
    {
        return $this->medicoNombre;
    }
}
