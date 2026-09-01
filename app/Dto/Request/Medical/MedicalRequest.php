<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalRequest
{
    public function __construct(
        private int $userId,
        private string $tuition,
        private int $specialtyId,
        private ?string $phone = null,
        private bool $isActive = true
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
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