<?php
namespace App\Dto\Request\MedicalSchedule;

use App\Dto\Request\PaginationRequest;

final readonly class MedicalScheduleFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $medicalId = null,
        private ?int $dayOfWeek = null,
        private ?bool $isActive = null,
        private ?string $search = null
    ) {}

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }
  public function getSearch(): ?string  
    {
        return $this->search;
    }

    public function hasSearch(): bool
{
    return !empty($this->search);
}

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function hasMedicalId(): bool
    {
        return !empty($this->medicalId);
    }

    public function hasDayOfWeek(): bool
    {
        return !empty($this->dayOfWeek);
    }

    public function hasIsActive(): bool
    {
        return $this->isActive !== null;
    }
}