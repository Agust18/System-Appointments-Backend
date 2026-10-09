<?php

namespace App\Exception\Service;

use Exception;

class ServiceAlreadyExistsException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("El servicio '{$name}' ya existe", 409);
    }
}
