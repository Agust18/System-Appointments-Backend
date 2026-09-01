<?php
namespace App\Exception\Medical;

use Exception;

class MedicalTuitionAlreadyExistsException extends Exception
{
    public function __construct(string $tuition)
    {
        parent::__construct("La matrícula '{$tuition}' ya está registrada", 409);
    }
}