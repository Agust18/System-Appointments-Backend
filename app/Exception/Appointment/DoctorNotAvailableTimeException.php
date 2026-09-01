<?php
namespace App\Exception\Appointment;

use Exception;

class DoctorNotAvailableTimeException extends Exception
{
    public function __construct(string $doctorName, string $startTime, string $endTime)
    {
        parent::__construct("El médico {$doctorName} solo atiende de {$startTime} a {$endTime}", 400);
    }
}