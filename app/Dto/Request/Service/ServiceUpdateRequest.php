<?php
namespace App\Dto\Request\Service;

final readonly class ServiceUpdateRequest
{
    public function __construct(
        private ?string $name = null,
        private ?string $description = null,
        private ?int $durationMinutes = null,
        private ?int $bufferMinutes = null,
        private ?float $price = null,
        private ?int $slotStepMinutes = null,
        private ?bool $isActive = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function getBufferMinutes(): ?int
    {
        return $this->bufferMinutes;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getSlotStepMinutes(): ?int
    {
        return $this->slotStepMinutes;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
}
