<?php
namespace App\Dto\Request\Role;

final readonly class RoleUpdateRequest
{
    public function __construct(
        private ?string $name = null
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }
}