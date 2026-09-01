<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalUpdateRequest
{
    public function __construct(
        private ?string $tuition = null,
        private ?int $specialtyId = null,
        private ?string $phone = null,
        private ?bool $isActive = null
    ) {}

    public function getTuition(): ?string
    {
        return $this->tuition;
    }

    public function getSpecialtyId(): ?int
    {
        return $this->specialtyId;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
}