<?php
namespace App\Dto\Response\DayOff;

final readonly class DayOffResponse
{
    public function __construct(
        public int $id,
        public ?int $medicalId,
        public string $dateFrom,
        public string $dateTo,
        public ?string $reason,
        public int $createdBy,
        public ?string $createdAt
    ) {}
}
