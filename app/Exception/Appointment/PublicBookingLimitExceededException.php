<?php
namespace App\Exception\Appointment;

use Exception;

class PublicBookingLimitExceededException extends Exception
{
    public function __construct(int $max)
    {
        parent::__construct(
            "Ya tenés {$max} turnos pendientes o confirmados. Para reservar otro, esperá a que se realice alguno o contactá al consultorio.",
            409
        );
    }
}
