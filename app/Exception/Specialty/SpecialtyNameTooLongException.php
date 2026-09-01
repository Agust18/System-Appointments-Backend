<?php
namespace App\Exception\Specialty;

use Exception;

class SpecialtyNameTooLongException extends Exception
{
    public function __construct(int $maxLength = 50)
    {
        parent::__construct("El nombre de la especialidad no puede exceder los {$maxLength} caracteres", 400);
    }
}