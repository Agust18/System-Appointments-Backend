<?php
namespace App\Dto\Response\MedicalService;

/**
 * Servicio que ofrece un médico, con la duración y el precio efectivos (override del
 * médico o base del servicio). Sin ids de la relación, buffer ni paso de agenda.
 */
final readonly class PublicMedicalServiceResponse
{
    public function __construct(
        public int $serviceId,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public float $price
    ) {}
}
