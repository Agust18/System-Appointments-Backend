<?php
namespace App\Dto\Response\Evolution\Projection;

final readonly class EvolutionProjectionResponse
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
        public ?string $updatedAt,
        public string $medicalName,
        public string $appointmentDate,
        public string $appointmentTime,
        public string $serviceName
    ) {}
}
