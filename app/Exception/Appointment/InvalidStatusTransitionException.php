<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * La transición de estado pedida no está en la tabla de transiciones permitidas.
 */
class InvalidStatusTransitionException extends Exception
{
    public function __construct()
    {
        parent::__construct('La transición de estado del turno no está permitida', 409);
    }
}
