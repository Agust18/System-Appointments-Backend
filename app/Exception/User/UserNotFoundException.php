<?php

namespace App\Exception\User;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(int $id)
    {  
        parent::__construct("No se encontro el usuario con ID : " . $id, 404);
    }
}
