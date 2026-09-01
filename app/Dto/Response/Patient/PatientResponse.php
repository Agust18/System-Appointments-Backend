<?php
namespace App\Dto\Response\Patient;

final readonly class PatientResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $birthDate,  
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}