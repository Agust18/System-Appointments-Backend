<?php
namespace App\Exception\Medical;

use Exception;

class MedicalHasAppointmentsException extends Exception
{
    public function __construct(int $count)
    {
        parent::__construct(
            "No se puede eliminar el médico porque tiene {$count} turnos asignados.",
            409
        );
    }
}