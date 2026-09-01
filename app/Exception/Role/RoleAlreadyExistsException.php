<?php
namespace App\Exception\Role;

use Exception;

class RoleAlreadyExistsException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("El rol '{$name}' ya existe", 409);
    }
}