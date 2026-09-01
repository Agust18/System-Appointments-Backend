<?php
namespace App\Dto\Request\MedicalSchedule;

final readonly class MedicalScheduleUpdateRequest
{
    public function __construct(
        public ?int $medicalId = null,
        public ?int $dayOfWeek = null,
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?bool $isActive = null
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