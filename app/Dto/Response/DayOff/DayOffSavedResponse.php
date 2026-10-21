<?php
namespace App\Dto\Response\DayOff;

/**
 * Respuesta del alta y la edición: el bloqueo más los turnos que quedaron adentro (no se tocan;
 * recepción los mueve o cancela a mano).
 */
final readonly class DayOffSavedResponse
{
    /**
     * @param AffectedAppointmentResponse[] $affectedAppointments
     */
    public function __construct(
        public int $id,
        public ?int $medicalId,
        public string $dateFrom,
        public string $dateTo,
        public ?string $reason,
        public int $createdBy,
        public ?string $createdAt,
        public array $affectedAppointments
    ) {}
}
