<?php
namespace App\Exception\Appointment;

use Exception;

class InvalidAppointmentDateException extends Exception
{
    public function __construct()
    {
        parent::__construct('La fecha proporcionada no es una fecha válida en el calendario', 422);
    }
}
