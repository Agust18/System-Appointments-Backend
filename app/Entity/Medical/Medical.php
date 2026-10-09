<?php
namespace App\Entity\Medical;

use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalUpdateRequest;
use DateTime;

final class Medical
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private string $tuition,
        private int $specialtyId,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }



    public function modify(MedicalUpdateRequest $request): void
    {
        $this->tuition = $request->getTuition() ?? $this->tuition;
        $this->specialtyId = $request->getSpecialtyId() ?? $this->specialtyId;
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


    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function convertFromRequest(int $userId, MedicalDataRequest $request): Medical
    {
        return new Medical(
            null,
            $userId,
            $request->getTuition(),
            $request->getSpecialtyId(),
            null,
            null
        );
    }
}