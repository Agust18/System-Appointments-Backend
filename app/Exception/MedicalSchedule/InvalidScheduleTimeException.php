<?php
namespace App\Exception\MedicalSchedule;

use Exception;

class InvalidScheduleTimeException extends Exception
{
    public function __construct()
    {
        parent::__construct("La hora de inicio debe ser menor que la hora de fin", 400);
    }
}