<?php
namespace App\Dto\Request\Service;

final readonly class ServiceUpdateRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?int $durationMinutes = null,
        public ?int $bufferMinutes = null,
        public ?float $price = null,
        public ?int $slotStepMinutes = null,
        public ?bool $isActive = null
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
