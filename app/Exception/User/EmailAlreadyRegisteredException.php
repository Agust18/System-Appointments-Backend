<?php
namespace App\Exception\User;

use Exception;

class EmailAlreadyRegisteredException extends Exception
{
    public function __construct()
    {
        parent::__construct('El email ya está registrado', 409);
    }
}