<?php
namespace App\Entity\MedicalService;

use App\Dto\Request\MedicalService\MedicalServiceRequest;
use DateTime;

final class MedicalService
{
    public function __construct(
        private ?int $id,
        private int $medicalId,
        private int $serviceId,
        private ?float $customPrice,
        private ?int $customDurationMinutes,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
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

    public static function fromRequest(MedicalServiceRequest $request, int $medicalId): MedicalService
    {
        return new MedicalService(
            null,
            $medicalId,
            $request->getServiceId(),
            $request->getCustomPrice(),
            $request->getCustomDurationMinutes(),
            $request->getIsActive(),
            new DateTime(),
            new DateTime()
        );
    }
}