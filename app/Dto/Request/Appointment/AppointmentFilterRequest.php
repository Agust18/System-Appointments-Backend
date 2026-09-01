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
}