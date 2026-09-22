<?php

namespace App\Dto\Response\Patient\Projection;

final readonly class PatientProjectionResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $pacienteNombre,
        public ?string $dni,
        public ?string $birthDate,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
