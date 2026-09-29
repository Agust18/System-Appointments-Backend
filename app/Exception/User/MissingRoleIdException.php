<?php
namespace App\Exception\User;

use Exception;

class MissingRoleIdException extends Exception
{
    public function __construct()
    {
        parent::__construct("El campo roleId es requerido", 400);
    }
}
