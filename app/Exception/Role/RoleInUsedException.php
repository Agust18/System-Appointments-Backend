<?php
namespace App\Exception\Role;

use Exception;

class RoleInUsedException extends Exception
{
    public function __construct(string $roleName, int $userCount)
    {
        parent::__construct("No se puede eliminar el rol '{$roleName}' porque tiene {$userCount} usuarios asignados", 409);
    }
}