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

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'search' => 'permit_empty|string|max_length[100]',
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
        ]);
    }
}