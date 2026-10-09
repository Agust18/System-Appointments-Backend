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

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'search'   => 'permit_empty|string|max_length[100]',
            'isActive' => 'permit_empty|in_list[0,1,true,false]',
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
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ]);
    }
}