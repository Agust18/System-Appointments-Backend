<?php
namespace App\Exception\Role;

use Exception;

class RoleNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el rol con ID: " . $id, 404);
    }
}