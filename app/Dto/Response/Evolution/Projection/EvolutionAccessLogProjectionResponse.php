<?php
namespace App\Dto\Response\Evolution\Projection;

final readonly class EvolutionAccessLogProjectionResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public int $patientId,
        public string $patientName,
        public ?int $evolutionId,
        public string $action,
        public string $createdAt
    ) {}
}
