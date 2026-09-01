<?php
namespace App\Exception\MedicalSchedule;

use Exception;

class InvalidDayOfWeekException extends Exception
{
    public function __construct(int $dayOfWeek)
    {
        parent::__construct("El día de la semana '{$dayOfWeek}' es inválido. Debe ser entre 1 (Lunes) y 7 (Domingo)", 400);
    }
}