<?php 
namespace App\Dto\Request\Patient;

use App\Dto\Request\PaginationRequest;

final readonly class PatientFilterRequest 
{
    public function __construct(
        private PaginationRequest $pagination,

        private ?string $search = null,
        private ?int $medicalUserId = null
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

    public function getMedicalUserId(): ?int
    {
        return $this->medicalUserId;
    }

    public function hasMedicalUserId(): bool
    {
        return $this->medicalUserId !== null;
    }
}