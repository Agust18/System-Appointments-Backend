<?php
namespace App\Exception\User;

use Exception;

class RoleMismatchException extends Exception
{
    public function __construct()
    {
        parent::__construct("El rol no coincide con el tipo de recurso que se quiere crear", 403);
    }
}
