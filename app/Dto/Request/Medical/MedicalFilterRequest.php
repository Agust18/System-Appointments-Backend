<?php 
namespace App\Dto\Request\Medical;

use App\Dto\Request\PaginationRequest;

final readonly class MedicalFilterRequest 
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $specialtyId = null,
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

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'specialtyId' => 'permit_empty|is_natural_no_zero',
            'search'      => 'permit_empty|string|max_length[100]',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'specialtyId' => [
                'is_natural_no_zero' => 'El ID de la especialidad debe ser un número entero mayor que cero.',
            ],
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
        ]);
    }
}