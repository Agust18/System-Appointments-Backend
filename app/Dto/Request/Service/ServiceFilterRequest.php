<?php
namespace App\Dto\Request\Service;

use App\Dto\Request\PaginationRequest;

final readonly class ServiceFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?string $search = null,
        private ?bool $isActive = null
    ) {
    }

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search !== '';
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function hasIsActive(): bool
    {
        return $this->isActive !== null;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
}