<?php
namespace App\Entity\MedicalSchedule\Projection;

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use DateTime;

final class MedicalScheduleProjection
{
    public function __construct(
        private ?int $id,
        private int $medicalId,
        private ?string $medicoNombre,
        private int $dayOfWeek,
        private string $startTime,
        private string $endTime,
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

    public function getMedicoNombre(): ?string 
    {
        return $this->medicoNombre;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
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
}