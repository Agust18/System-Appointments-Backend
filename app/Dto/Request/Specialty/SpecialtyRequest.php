<?php
namespace App\Dto\Request\Specialty;

final readonly class SpecialtyRequest
{
    public function __construct(
        private string $name,
        private ?string $description = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}