<?php 
namespace App\Dto\Request\User;

use App\Dto\Request\PaginationRequest;

final readonly class UserFilterRequest {
    public function __construct(
        private PaginationRequest $pagination,
        private ?string $email,
        private ?string $search,    
        private ?int $roleId ,        
        private ?bool $isActive      
    ) {}

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function hasRoleId(): bool
    {
        return !empty($this->roleId);
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function hasIsActive(): bool
    {
        return $this->isActive !== null;
    }
}