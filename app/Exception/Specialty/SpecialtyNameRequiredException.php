<?php
namespace App\Exception\Specialty;

use Exception;

class SpecialtyNameRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('El nombre de la especialidad es requerido', 400);
    }
}