<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Un médico intentó llevar el turno a un estado distinto de completed o cancelled.
 */
class MedicalCanOnlyCompleteException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los médicos solo pueden completar o cancelar el turno', 403);
    }
}
