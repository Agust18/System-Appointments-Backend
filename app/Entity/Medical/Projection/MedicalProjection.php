<?php
namespace App\Entity\Medical\Projection;

use DateTime;

final class MedicalProjection
{
    public function __construct(
        // IDs (para operaciones)
        private ?int $id,
        private int $userId,
        private int $specialtyId,

        // Proyecciones (nombres para mostrar)
        private string $medicoNombre,
        private string $especialidadNombre,

        // Datos del médico
        private string $tuition,
        private ?string $phone,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    // Getters para IDs
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getSpecialtyId(): int
    {
        return $this->specialtyId;
    }

    // Getters para proyecciones (nombres)
    public function getMedicoNombre(): string
    {
        return $this->medicoNombre;
    }
    public function getEspecialidadNombre(): string
    {
        return $this->especialidadNombre;
    }

    // Getters para datos del médico
    public function getTuition(): string
    {
        return $this->tuition;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}