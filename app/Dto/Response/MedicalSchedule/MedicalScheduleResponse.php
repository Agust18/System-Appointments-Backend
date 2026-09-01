<?php
namespace App\Dto\Response\MedicalSchedule;

final readonly class MedicalScheduleResponse
{
    public function __construct(
        public int $id,
        public int $medicalId,
        public int $dayOfWeek,
        public string $startTime,
        public string $endTime,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}