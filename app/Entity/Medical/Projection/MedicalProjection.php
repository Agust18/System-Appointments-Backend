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
        private ?string $email,
        private ?string $phone,
        private string $especialidadNombre,

        // Datos del médico
        private string $tuition,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt,

        // Estado del usuario del médico (única fuente de verdad: users.is_active)
        private bool $userIsActive = true
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
    public function getEmail(): ?string
    {
        return $this->email;
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
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
    public function isUserActive(): bool
    {
        return $this->userIsActive;
    }
}