<?php
namespace App\Exception\Role;

use Exception;

class RoleNameTooShortException extends Exception
{
    public function __construct(int $minLength = 3)
    {
        parent::__construct("El nombre del rol debe tener al menos {$minLength} caracteres", 400);
    }
}