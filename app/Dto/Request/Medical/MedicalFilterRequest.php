<?php 
namespace App\Dto\Request\Medical;

use App\Dto\Request\PaginationRequest;

final readonly class MedicalFilterRequest 
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $specialtyId = null,
        private ?bool $isActive = null,
        private ?string $search = null  
    ) {}

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }

    public function getSpecialtyId(): ?int
    {
        return $this->specialtyId;
    }

    public function hasSpecialtyId(): bool
    {
        return !empty($this->specialtyId);
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function hasIsActive(): bool
    {
        return $this->isActive !== null;
    }

 
    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }
}