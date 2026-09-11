<?php
namespace App\Exception\Appointment;

use Exception;

class AppointmentAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'El horario seleccionado ya no se encuentra disponible. Por favor, elija otro horario.',
            409
        );
    }
}