<?php
namespace App\Dto\Request\MedicalSchedule;

final readonly class MedicalScheduleRequest
{
    public function __construct(
        private int $medicalId,
        private int $dayOfWeek,
        private string $startTime,
        private string $endTime,
        private bool $isActive = true
    ) {
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
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
    public function getIsActive(): bool
    {
        return $this->isActive;
    }
}