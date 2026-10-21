<?php
namespace App\Entity\Evolution\Projection;

use DateTime;

/** Solo lectura: una fila del registro de accesos con los nombres de quien accedió y del paciente. Nunca se persiste. */
final class EvolutionAccessLogProjection
{
    public function __construct(
        private int $id,
        private int $userId,
        private string $userName,
        private int $patientId,
        private string $patientName,
        private ?int $evolutionId,
        private string $action,
        private DateTime $createdAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getPatientName(): string
    {
        return $this->patientName;
    }

    public function getEvolutionId(): ?int
    {
        return $this->evolutionId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
