<?php
namespace App\Entity\DayOff;

use App\Dto\Request\DayOff\DayOffRequest;
use App\Dto\Request\DayOff\DayOffUpdateRequest;
use DateTime;

final class DayOff
{
    public function __construct(
        private ?int $id,
        private ?int $medicalId,
        private string $dateFrom,
        private string $dateTo,
        private ?string $reason,
        private int $createdBy,
        private ?DateTime $createdAt
    ) {
    }

    public static function fromRequest(DayOffRequest $request, int $createdBy): DayOff
    {
        return new DayOff(
            null,
            $request->getMedicalId(),
            $request->getDateFrom(),
            $request->getDateTo(),
            $request->getReason(),
            $createdBy,
            null
        );
    }

    public function modify(DayOffUpdateRequest $request): void
    {
        if ($request->hasMedicalId()) {
            $this->medicalId = $request->getMedicalId();
        }
        if ($request->getDateFrom() !== null) {
            $this->dateFrom = $request->getDateFrom();
        }
        if ($request->getDateTo() !== null) {
            $this->dateTo = $request->getDateTo();
        }
        if ($request->hasReason()) {
            $this->reason = $request->getReason();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function getDateFrom(): string
    {
        return $this->dateFrom;
    }

    public function getDateTo(): string
    {
        return $this->dateTo;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
}
