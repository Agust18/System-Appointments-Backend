<?php

namespace App\exception\Patient;
use Exception;

class PatientNotFoundExceptionByUserException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("No se encontro el paciente con el ID de usuario: " . $userId, 404);
    }
}