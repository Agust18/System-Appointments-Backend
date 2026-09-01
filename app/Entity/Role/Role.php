<?php
namespace App\Entity\Role;

use App\Dto\Request\Role\RoleRequest;

final class Role
{
    public function __construct(
        private ?int $id,
        private string $name
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public static function fromRequest(RoleRequest $request): Role
    {
        return new Role(
            null,
            $request->getName()
        );
    }

    public function updateFromRequest(RoleRequest $request): void
    {
        $this->name = $request->getName();
    }
}