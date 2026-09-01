<?php
namespace App\Exception\Role;

use Exception;

class RoleNameTooLongException extends Exception
{
    public function __construct(int $maxLength = 20)
    {
        parent::__construct("El nombre del rol no puede exceder los {$maxLength} caracteres", 400);
    }
}