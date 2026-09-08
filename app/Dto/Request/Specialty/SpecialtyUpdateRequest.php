<?php
namespace App\Dto\Request\Specialty;

final readonly class SpecialtyUpdateRequest
{
    public function __construct(
        private ?string $name = null,
        private ?string $description = null
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}