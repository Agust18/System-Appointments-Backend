<?php
namespace App\Exception\MedicalSchedule;

class ScheduleAlreadyExistsException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Ya existe un horario con este día y hora para el médico');
    }
}