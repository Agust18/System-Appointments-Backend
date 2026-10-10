<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Un médico intentó modificar campos distintos del estado del turno.
 */
class MedicalCanOnlyChangeStatusException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los médicos solo pueden modificar el estado del turno', 403);
    }
}
