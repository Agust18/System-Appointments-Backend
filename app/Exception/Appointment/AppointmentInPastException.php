<?php
namespace App\Exception\Appointment;

use App\Exception\HasErrorCode;
use Exception;

class AppointmentInPastException extends Exception implements HasErrorCode
{
    public function errorCode(): string
    {
        return 'PAST_DATE';
    }

    public function __construct()
    {
        parent::__construct(
            'No se pueden crear turnos en fechas y horas pasadas',
            409,
        );
    }
}