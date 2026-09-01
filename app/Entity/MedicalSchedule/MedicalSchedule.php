<?php
namespace App\Entity\MedicalSchedule;

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use DateTime;

final class MedicalSchedule
{
    public function __construct(
        private ?int $id,
        private int $medicalId,
        private int $dayOfWeek,
        private string $startTime,
        private string $endTime,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }


    public static function fromRequest(MedicalScheduleRequest $request): MedicalSchedule
    {
        return new MedicalSchedule(
            null,
            $request->getMedicalId(),
            $request->getDayOfWeek(),
            $request->getStartTime(),
            $request->getEndTime(),
            $request->getIsActive(),
            null,
            null
        );
    }

    public function modify(MedicalScheduleUpdateRequest $request): void
    {
        if ($request->getMedicalId() !== null) {
            $this->medicalId = $request->getMedicalId();
        }
        if ($request->getDayOfWeek() !== null) {
            $this->dayOfWeek = $request->getDayOfWeek();
        }
        if ($request->getStartTime() !== null) {
            $this->startTime = $request->getStartTime();
        }
        if ($request->getEndTime() !== null) {
            $this->endTime = $request->getEndTime();
        }
        if ($request->getIsActive() !== null) {
            $this->isActive = $request->getIsActive();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setMedicalId(int $medicalId): void
    {
        $this->medicalId = $medicalId;
    }

    public function setDayOfWeek(int $dayOfWeek): void
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}