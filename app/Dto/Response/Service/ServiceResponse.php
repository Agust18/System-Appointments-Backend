<?php
namespace App\Dto\Response\Service;

final readonly class ServiceResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public int $bufferMinutes,
        public float $price,
        public int $slotStepMinutes,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {
    }
}
