<?php
namespace App\Dto\Response\DayOff;

final readonly class AffectedAppointmentResponse
{
    public function __construct(
        public int $id,
        public string $fecha,
        public string $hora,
        public string $pacienteNombre,
        public int $medicalId,
        public string $medicoNombre
    ) {}
}
