<?php
namespace App\Exception\Appointment;

use Exception;

class CannotCreateAppointmentForOtherPatientException extends Exception
{
    public function __construct()
    {
        parent::__construct('No podés crear un turno para otro paciente', 403);
    }
}
