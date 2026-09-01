<?php
namespace App\Dto\Request\Role;

final readonly class RoleUpdateRequest
{
    public function __construct(
        public ?string $name = null
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }
}