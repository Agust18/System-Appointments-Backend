<?php

namespace App\Dto\Request\Platform;

use App\Dto\Request\PaginationRequest;

final readonly class TenantFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?string $search,
        private ?string $status,
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function hasStatus(): bool
    {
        return !empty($this->status);
    }

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'search' => 'permit_empty|string|max_length[100]',
            'status' => 'permit_empty|in_list[active,suspended]',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
            'status' => [
                'in_list' => 'El estado debe ser active o suspended.',
            ],
        ]);
    }
}
