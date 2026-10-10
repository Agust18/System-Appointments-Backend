<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Un paciente intentó llevar el turno a un estado distinto de cancelled.
 */
class PatientCanOnlyCancelException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los pacientes solo pueden cancelar turnos', 403);
    }
}
