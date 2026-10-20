<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * El turno ya está completado o cancelado y no admite cambios.
 */
class AppointmentNotModifiableException extends Exception
{
    public function __construct()
    {
        parent::__construct('No se puede modificar un turno completado o cancelado', 409);
    }
}
