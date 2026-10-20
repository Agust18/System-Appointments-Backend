<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * El paciente intentó cancelar un turno que ya pasó, o que ya está cancelado o completado.
 */
class AppointmentNotCancellableException extends Exception
{
    public function __construct()
    {
        parent::__construct('Este turno ya no se puede cancelar. Si lo necesitás, comunicate con el consultorio.', 409);
    }
}
