<?php
namespace App\Dto\Request\Role;

use App\Dto\Request\PaginationRequest;

final readonly class RoleFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
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
}