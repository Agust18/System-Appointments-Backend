<?php
namespace App\Dto\Response\Patient;

final readonly class PatientResponse
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public ?string $email,
        public int $dni,
        public ?string $phone,
        public ?string $birthDate,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
