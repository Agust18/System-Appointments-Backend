<?php
namespace App\Dto\Request\Service;

final readonly class ServiceRequest
{
    public function __construct(
        private string $name,
        private ?string $description,
        private int $durationMinutes,
        private int $bufferMinutes = 0,
        private float $price = 0.0,
        private int $slotStepMinutes = 15,
        private bool $isActive = true
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
