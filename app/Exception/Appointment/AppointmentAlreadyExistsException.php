<?php
namespace App\Exception\Appointment;

use Exception;

class AppointmentAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct("Ya existe un turno para ese médico en esa fecha y hora", 409);
    }
}