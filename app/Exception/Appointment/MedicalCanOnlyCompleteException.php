<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Un médico intentó llevar el turno a un estado distinto de completed.
 */
class MedicalCanOnlyCompleteException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los médicos solo pueden marcar el turno como completado', 403);
    }
}
