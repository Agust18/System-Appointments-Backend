<?php
namespace App\Exception\Specialty;

use Exception;

class SpecialtyAlreadyExistsException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("La especialidad '".$name."' ya existe", 409);
    }
}