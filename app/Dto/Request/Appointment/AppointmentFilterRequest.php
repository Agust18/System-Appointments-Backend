<?php

namespace App\Dto\Request\Appointment;

use App\Dto\Request\PaginationRequest;

final readonly class AppointmentFilterRequest
{
    public function __construct(
        private PaginationRequest $pagination,
        private ?int $medicalId = null,
        private ?int $patientId = null,
        private ?string $status = null,
        private ?string $fecha = null,
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
    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }
    public function getPatientId(): ?int
    {
        return $this->patientId;
    }
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function getFecha(): ?string
    {
        return $this->fecha;
    }

    public function hasMedicalId(): bool
    {
        return !empty($this->medicalId);
    }
    public function hasPatientId(): bool
    {
        return !empty($this->patientId);
    }
    public function hasStatus(): bool
    {
        return !empty($this->status);
    }
    public function hasFecha(): bool
    {
        return !empty($this->fecha);
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
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'patientId' => 'permit_empty|is_natural_no_zero',
            'status'    => 'permit_empty|string|in_list[pending,confirmed,cancelled,completed]',
            'fecha'     => 'permit_empty|valid_date[Y-m-d]',
            'search'    => 'permit_empty|string|max_length[100]',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return array_merge(PaginationRequest::messages(), [
            'medicalId' => [
                'is_natural_no_zero' => 'El ID del médico debe ser un número entero mayor que cero.',
            ],
            'patientId' => [
                'is_natural_no_zero' => 'El ID del paciente debe ser un número entero mayor que cero.',
            ],
            'status' => [
                'in_list' => 'El estado debe ser uno de: pendiente, confirmado, cancelado, completado.',
            ],
            'fecha' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 2025-01-31).',
            ],
            'search' => [
                'max_length' => 'La búsqueda no puede superar los 100 caracteres.',
            ],
        ]);
    }
}
