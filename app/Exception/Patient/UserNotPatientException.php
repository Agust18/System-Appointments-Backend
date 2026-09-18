<?php
namespace App\Exception\Patient;

use Exception;

class UserNotPatientException extends Exception
{
    public function __construct()
    {
        parent::__construct("El usuario no tiene el rol de paciente", 403);
    }
}