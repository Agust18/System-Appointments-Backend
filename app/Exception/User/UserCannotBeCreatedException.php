<?php
namespace App\Exception\User;

use Exception;

class UserCannotBeCreatedException extends Exception
{
    public function __construct()
    {
        parent::__construct('No se pudo crear el usuario', 500);
    }
}
