<?php
namespace App\Exception\Appointment;

use Exception;

class BookingLockTimeoutException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'El sistema está ocupado procesando otra reserva para este profesional. Por favor, intente nuevamente en unos segundos.',
            423
        );
    }
}
