<?php
namespace App\Entity\MedicalService\Projection;

final class MedicalServiceProjection
{
    public function __construct(
        private int $medicalServiceId,
        private int $medicalId,
        private int $serviceId,
        private string $name,
        private ?string $description,
        private int $baseDurationMinutes,
        private int $bufferMinutes,
        private float $basePrice,
        private int $slotStepMinutes,
        private ?float $customPrice,
        private ?int $customDurationMinutes,
        private int $effectiveDurationMinutes,
        private float $effectivePrice,
        private bool $isActive
    ) {
    }

    public function getMedicalServiceId(): int
    {
        return $this->medicalServiceId;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBaseDurationMinutes(): int
    {
        return $this->baseDurationMinutes;
    }

    public function getBufferMinutes(): int
    {
        return $this->bufferMinutes;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getSlotStepMinutes(): int
    {
        return $this->slotStepMinutes;
    }

    public function getCustomPrice(): ?float
    {
        return $this->customPrice;
    }

    public function getCustomDurationMinutes(): ?int
    {
        return $this->customDurationMinutes;
    }

    public function getEffectiveDurationMinutes(): int
    {
        return $this->effectiveDurationMinutes;
    }

    public function getEffectivePrice(): float
    {
        return $this->effectivePrice;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}