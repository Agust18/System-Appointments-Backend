<?php
namespace App\Dto\Response\Specialty;

final readonly class SpecialtyResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}