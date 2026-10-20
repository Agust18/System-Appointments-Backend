<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Se intentó reprogramar un turno a una fecha pasada.
 */
class PastAppointmentCannotBeModifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('No se puede modificar un turno con fecha pasada', 409);
    }
}
