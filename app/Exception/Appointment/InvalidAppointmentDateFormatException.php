<?php
namespace App\Exception\Appointment;

use Exception;

class InvalidAppointmentDateFormatException extends Exception
{
    public function __construct()
    {
        parent::__construct('El formato de fecha debe ser YYYY-MM-DD', 422);
    }
}
