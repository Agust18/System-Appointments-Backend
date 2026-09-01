<?php
namespace App\Dto\Request\MedicalService;

final readonly class MedicalServiceRequest
{
    public function __construct(
        public int $serviceId,
        public ?float $customPrice = null,
        public ?int $customDurationMinutes = null,
        public bool $isActive = true
    ) {
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getCustomPrice(): ?float
    {
        return $this->customPrice;
    }

    public function getCustomDurationMinutes(): ?int
    {
        return $this->customDurationMinutes;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }
}