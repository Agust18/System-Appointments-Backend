<?php
namespace App\Dto\Response\MedicalSchedule\Projection;

final readonly class MedicalScheduleProjectionResponse
{
    public function __construct(
        public int $id,
        public int $medicalId,
        public ?string $medicoNombre, 
        public int $dayOfWeek,
        public string $startTime,
        public string $endTime,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}