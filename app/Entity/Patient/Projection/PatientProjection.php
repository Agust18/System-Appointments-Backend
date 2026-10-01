<?php

namespace App\Entity\Patient\Projection;

use DateTime;

final class PatientProjection
{
    public function __construct(
        private int $id,
        private int $userId,
        private string $pacienteNombre,
        private ?string $phone,
        private ?string $dni,
        private ?string $email,
        private ?DateTime $birthDate,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getPacienteNombre(): string
    {
        return $this->pacienteNombre;
    }
    public function getDni(): ?string
    {
        return $this->dni;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
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
