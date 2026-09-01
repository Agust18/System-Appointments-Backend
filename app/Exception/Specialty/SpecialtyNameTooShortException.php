<?php
namespace App\Exception\Specialty;

use Exception;

class SpecialtyNameTooShortException extends Exception
{
    public function __construct(int $minLength = 3)
    {
        parent::__construct("El nombre de la especialidad debe tener al menos {$minLength} caracteres", 400);
    }
}