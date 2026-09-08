<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalDataRequest
{
    public function __construct(
       private string $tuition,
       private int $specialtyId,
       private ?string $phone = null,
       private bool $isActive = true
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