<?php
namespace App\Dto\Request\MedicalService;

final readonly class MedicalServiceRequest
{
    public function __construct(
        private int $serviceId,
        private ?float $customPrice = null,
        private ?int $customDurationMinutes = null,
        private bool $isActive = true
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