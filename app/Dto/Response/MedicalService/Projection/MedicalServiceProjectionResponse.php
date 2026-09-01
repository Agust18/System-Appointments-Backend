<?php
namespace App\Dto\Response\MedicalService\Projection;

final readonly class MedicalServiceProjectionResponse
{
    public function __construct(
        public int $medicalServiceId,
        public int $medicalId,
        public int $serviceId,
        public string $name,
        public ?string $description,
        public int $baseDurationMinutes,
        public int $bufferMinutes,
        public float $basePrice,
        public int $slotStepMinutes,
        public ?float $customPrice,
        public ?int $customDurationMinutes,
        public int $effectiveDurationMinutes,
        public float $effectivePrice,
        public bool $isActive
    ) {
    }
}
