<?php
namespace App\Exception\Role;

use Exception;

class RoleNameRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('El nombre del rol es requerido', 400);
    }
}