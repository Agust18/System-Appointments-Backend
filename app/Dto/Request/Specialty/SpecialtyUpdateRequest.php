<?php
namespace App\Dto\Request\Specialty;

final readonly class SpecialtyUpdateRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null
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