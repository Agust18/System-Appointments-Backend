<?php
namespace App\Exception\Appointment;

use Exception;

class PatientCannotBeMedicalException extends Exception
{
    public function __construct()
    {
        parent::__construct('El paciente no puede ser el mismo que el médico', 422);
    }
}
