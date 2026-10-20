<?php

namespace App\Exception\Patient;

use Exception;

class PatientHasAppointmentsException extends Exception
{
    public function __construct(int $count)
    {
        parent::__construct(
            "No se puede eliminar el paciente porque tiene {$count} turnos asignados.",
            409
        );
    }
}
