<?php
namespace App\Exception\Patient;

use Exception;

class UserNotPatientException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("El usuario con ID {$userId} no tiene el rol de paciente", 403);
    }
}