<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalDataRequest
{
    public function __construct(
        public string $tuition,
        public int $specialtyId,
        public ?string $phone = null,
        public bool $isActive = true
    ) {
    }

    public function getTuition(): string
    {
        return $this->tuition;
    }
    public function getSpecialtyId(): int
    {
        return $this->specialtyId;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function getIsActive(): bool
    {
        return $this->isActive;
    }
}