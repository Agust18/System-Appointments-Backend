<?php
namespace App\Dto\Request\MedicalSchedule;

final readonly class MedicalScheduleUpdateRequest
{
    public function __construct(
        private ?int $medicalId = null,
        private ?int $dayOfWeek = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
        private ?bool $isActive = null
    ) {
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }
    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }
    public function getStartTime(): ?string
    {
        return $this->startTime;
    }
    public function getEndTime(): ?string
    {
        return $this->endTime;
    }
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
}