<?php
namespace App\Dto\Request\DayOff;

use App\Dto\Request\PaginationRequest;

/**
 * `medicalId` trae los bloqueos que afectan a ese médico: los suyos más los de toda la clínica.
 * `from` / `to` traen los que se solapan con ese rango (cualquiera de los dos puede faltar).
 */
final readonly class DayOffFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $medicalId = null,
        private ?string $from = null,
        private ?string $to = null
    ) {
    }

    public function getPagination(): PaginationRequest
    {
        return $this->pagination;
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function withMedicalId(int $medicalId): self
    {
        return new self($this->pagination, $medicalId, $this->from, $this->to);
    }

    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(), [
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'from'      => 'permit_empty|valid_date[Y-m-d]',
            'to'        => 'permit_empty|valid_date[Y-m-d]',
        ]);
    }

    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'medicalId' => [
                'is_natural_no_zero' => 'El ID del médico debe ser un número entero mayor que cero.',
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
