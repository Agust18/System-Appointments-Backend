<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionAppointmentNotEligibleException extends Exception
{
    public function __construct(string $status)
    {
        parent::__construct("No se puede registrar una evolución sobre un turno en estado '{$status}'. El turno debe estar confirmado o completado.", 409);
    }
}
