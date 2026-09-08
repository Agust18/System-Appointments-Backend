<?php
namespace App\Dto\Request\Role;

final readonly class RoleRequest
{
    public function __construct(
        private string $name
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}