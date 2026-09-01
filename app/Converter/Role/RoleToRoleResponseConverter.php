<?php
namespace App\Converter\Role;

use App\Dto\Response\Role\RoleResponse;
use App\Entity\Role\Role;

final class RoleToRoleResponseConverter
{
    public function convert(Role $role): RoleResponse
    {
        return new RoleResponse(
            $role->getId(),
            $role->getName()
        );
    }
}