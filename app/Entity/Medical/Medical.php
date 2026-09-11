<?php
namespace App\Entity\Medical;

use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Request\Medical\MedicalUpdateRequest;
use DateTime;

final class Medical
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private string $tuition,
        private int $specialtyId,
        private ?string $phone,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }



    public function modify(MedicalUpdateRequest $request): void
    {
        $this->tuition = $request->getTuition() ?? $this->tuition;
        $this->specialtyId = $request->getSpecialtyId() ?? $this->specialtyId;
        $this->phone = $request->getPhone() ?? $this->phone;
        $this->isActive = $request->getIsActive() ?? $this->isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function modifyIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function convertFromRequest(MedicalRequest $request): Medical
    {
        return new Medical(
            null,
            $request->getUserId(),
            $request->getTuition(),
            $request->getSpecialtyId(),
            $request->getPhone(),
            $request->getIsActive(),
            null,
            null
        );
    }
}