<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * Un médico intentó modificar campos distintos del estado y la nota del turno.
 */
class MedicalCanOnlyChangeStatusException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los médicos solo pueden modificar el estado y la nota del turno', 403);
    }
}
