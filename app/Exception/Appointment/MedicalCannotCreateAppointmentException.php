<?php
namespace App\Exception\Appointment;

use Exception;

class MedicalCannotCreateAppointmentException extends Exception
{
    public function __construct()
    {
        parent::__construct('Los Profesionales no pueden crear turnos', 403);
    }
}
