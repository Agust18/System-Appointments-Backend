<?php
namespace App\Exception\User;

use Exception;

class UsernameAlreadyRegisteredException extends Exception
{
    public function __construct()
    {
        parent::__construct('El nombre de usuario ya está registrado', 409);
    }
}
