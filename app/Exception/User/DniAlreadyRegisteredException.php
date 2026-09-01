<?php
namespace App\Exception\User;

use Exception;

class DniAlreadyRegisteredException extends Exception
{
    public function __construct()
    {
        parent::__construct('El DNI ya está registrado en el sistema', 409);
    }
}