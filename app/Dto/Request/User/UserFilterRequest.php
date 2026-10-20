<?php 
namespace App\Dto\Request\User;

use App\Constants\Role;
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

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'email'    => 'permit_empty|string|max_length[150]',
            'search'   => 'permit_empty|string|max_length[100]',
            'roleId'   => 'permit_empty|' . Role::inListRule(),
            'isActive' => 'permit_empty|in_list[0,1,true,false]',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'email' => [
                'max_length' => 'El email es muy largo.',
            ],
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
            'roleId' => [
                'in_list' => 'El rol no es válido.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ]);
    }
}