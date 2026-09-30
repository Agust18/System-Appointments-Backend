<?php
namespace App\Exception\Appointment;

use Exception;

class AppointmentServiceRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'Este turno no tiene un servicio asociado; debe indicar un servicio para poder editarlo.',
            422
        );
    }
}
