<?php
namespace App\Exception\User;

use Exception;

class InvalidPasswordException extends Exception
{
    public function __construct()
    {
        parent::__construct('La contraseña debe tener al menos 6 caracteres', 400);
    }
}