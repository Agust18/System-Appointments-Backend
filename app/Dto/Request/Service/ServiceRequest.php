<?php
namespace App\Dto\Request\Service;

final readonly class ServiceRequest
{
    public function __construct(
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public int $bufferMinutes = 0,
        public float $price = 0.0,
        public int $slotStepMinutes = 15,
        public bool $isActive = true
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getBufferMinutes(): int
    {
        return $this->bufferMinutes;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getSlotStepMinutes(): int
    {
        return $this->slotStepMinutes;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }
}
