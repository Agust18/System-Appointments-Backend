<?php
namespace App\Dto\Response\Role;

final readonly class RoleResponse
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}