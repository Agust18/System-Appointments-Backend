<?php
namespace App\Entity\Service;

use App\Dto\Request\Service\ServiceRequest;
use App\Dto\Request\Service\ServiceUpdateRequest;
use DateTime;

final class Service
{
    public function __construct(
        private ?int $id,
        private string $name,
        private ?string $description,
        private int $durationMinutes,
        private int $bufferMinutes,
        private float $price,
        private int $slotStepMinutes,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    public function modify(ServiceUpdateRequest $request): void
    {
        if ($request->getName() !== null) {
            $this->name = $request->getName();
        }
        if ($request->getDescription() !== null) {
            $this->description = $request->getDescription();
        }
        if ($request->getDurationMinutes() !== null) {
            $this->durationMinutes = $request->getDurationMinutes();
        }
        if ($request->getBufferMinutes() !== null) {
            $this->bufferMinutes = $request->getBufferMinutes();
        }
        if ($request->getPrice() !== null) {
            $this->price = $request->getPrice();
        }
        if ($request->getSlotStepMinutes() !== null) {
            $this->slotStepMinutes = $request->getSlotStepMinutes();
        }
        if ($request->getIsActive() !== null) {
            $this->isActive = $request->getIsActive();
        }
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function fromRequest(ServiceRequest $request): Service
    {
        return new Service(
            null,
            $request->getName(),
            $request->getDescription(),
            $request->getDurationMinutes(),
            $request->getBufferMinutes(),
            $request->getPrice(),
            $request->getSlotStepMinutes(),
            $request->getIsActive(),
            new DateTime(),
            new DateTime()
        );
    }
}
