<?php
namespace App\Dto\Request\Evolution;

use App\Dto\Request\PaginationRequest;

/**
 * `search` busca por nombre del usuario que accedió, nombre o DNI del paciente.
 * `from` / `to` (inclusive) acotan por el día del acceso; cualquiera de los dos puede faltar.
 */
final readonly class EvolutionAccessLogFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?string $search = null,
        private ?string $from = null,
        private ?string $to = null
    ) {
    }

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'search' => 'permit_empty|max_length[100]',
            'from'   => 'permit_empty|valid_date[Y-m-d]',
            'to'     => 'permit_empty|valid_date[Y-m-d]',
        ]);
    }

    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
            'from' => [
                'valid_date' => 'La fecha "desde" no es válida.',
            ],
            'to' => [
                'valid_date' => 'La fecha "hasta" no es válida.',
            ],
        ]);
    }
}
