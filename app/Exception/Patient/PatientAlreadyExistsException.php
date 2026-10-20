<?php
namespace App\Exception\Patient;

use Exception;

class PatientAlreadyExistsException extends Exception
{
    public function __construct(int $dni)
    {
        parent::__construct("Ya existe un paciente con el DNI {$dni}", 409);
    }
}
