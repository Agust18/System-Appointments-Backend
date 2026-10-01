<?php
namespace App\Dto\Response\Medical;

final readonly class MedicalResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $tuition,
        public int $specialtyId,
        public bool $isActive
    ) {}
}