<?php
namespace App\Dto\Response\Evolution;

final readonly class EvolutionResponse
{
    public function __construct(
        public int $id,
        public int $patientId,
        public int $medicalId,
        public int $appointmentId,
        public ?string $diagnosis,
        public ?string $observations,
        public ?string $treatment,
        public ?string $indications,
        public ?int $correctsId,
        public string $status,
        public ?string $signedAt,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
