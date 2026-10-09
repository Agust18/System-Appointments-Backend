<?php
namespace App\Dto\Request\MedicalSchedule;

use App\Dto\Request\PaginationRequest;

final readonly class MedicalScheduleFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $medicalId = null,
        private ?int $dayOfWeek = null,
        private ?bool $isActive = null,
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

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function hasMedicalId(): bool
    {
        return !empty($this->medicalId);
    }

    public function hasDayOfWeek(): bool
    {
        return !empty($this->dayOfWeek);
    }

    public function hasIsActive(): bool
    {
        return $this->isActive !== null;
    }

    /**
     * Tope de `size` de este listado: 500 y no el default de 100. El dashboard
     * (useOfficeHours del frontend) pide todos los horarios activos de una vez con
     * size=500 y su query corre con `retry: false`; con tope 100 recibiría un 422 y
     * el heatmap pasaría a "todo abierto" sin avisar.
     */
    private const MAX_SIZE = 500;

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return array_merge(PaginationRequest::rules(self::MAX_SIZE), [
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'dayOfWeek' => 'permit_empty|in_list[1,2,3,4,5,6,7]',
            'isActive'  => 'permit_empty|in_list[0,1,true,false]',
            'search'    => 'permit_empty|string|max_length[100]',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(self::MAX_SIZE), [
            'medicalId' => [
                'is_natural_no_zero' => 'El ID del médico debe ser un número entero mayor que cero.',
            ],
            'dayOfWeek' => [
                'in_list' => 'El día de la semana debe ser un número del 1 al 7.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
        ]);
    }
}