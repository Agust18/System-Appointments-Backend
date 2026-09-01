<?php
namespace App\Converter\Role;

use App\Entity\Role\Role;

final class PrimitiveToRoleConverter
{
    public function convert(object $primitive): Role
    {
        return new Role(
            $primitive->id,
            $primitive->name
        );
    }
}