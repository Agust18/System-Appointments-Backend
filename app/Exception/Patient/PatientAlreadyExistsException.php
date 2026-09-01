<?php
namespace App\Exception\Patient;

use Exception;

class PatientAlreadyExistsException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("El usuario con ID {$userId} ya tiene un perfil de paciente", 409);
    }
}