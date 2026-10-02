<?php
namespace App\Dto\Response\Service;

/**
 * Servicio tal como lo ve quien reserva sin cuenta: sin buffer, paso de agenda,
 * estado ni timestamps (configuración interna).
 */
final readonly class PublicServiceResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public float $price
    ) {}
}
