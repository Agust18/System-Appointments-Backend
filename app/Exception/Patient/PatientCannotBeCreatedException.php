<?php
namespace App\Exception\Patient;

use Exception;

class PatientCannotBeCreatedException extends Exception
{
    public function __construct()
    {
        parent::__construct('No se pudo crear el paciente', 500);
    }
}
