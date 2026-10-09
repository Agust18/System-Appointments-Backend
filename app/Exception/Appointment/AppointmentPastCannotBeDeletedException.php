<?php
namespace App\Exception\Appointment;

use Exception;

class AppointmentPastCannotBeDeletedException extends Exception
{
    public function __construct()
    {
        parent::__construct('No se puede eliminar un turno que ya pasó', 403);
    }
}
