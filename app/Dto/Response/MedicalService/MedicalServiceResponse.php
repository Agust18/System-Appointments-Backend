<?php
namespace App\Dto\Response\MedicalService;

final readonly class MedicalServiceResponse
{
    public function __construct(
        public int $id,
        public int $medicalId,
        public int $serviceId,
        public ?float $customPrice,
        public ?int $customDurationMinutes,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {
    }
}