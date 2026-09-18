<?php
namespace App\Exception\Appointment;

use Exception;

class AppointmentNotPast extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'No se pueden crear turnos en fechas y horas pasadas',
            409,
        );
    }
}