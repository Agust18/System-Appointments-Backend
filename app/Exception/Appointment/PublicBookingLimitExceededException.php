<?php
namespace App\Exception\Appointment;

use App\Exception\HasErrorCode;
use Exception;

class PublicBookingLimitExceededException extends Exception implements HasErrorCode
{
    public function errorCode(): string
    {
        return 'BOOKING_LIMIT_REACHED';
    }

    public function __construct(int $max)
    {
        parent::__construct(
            "Ya tenés {$max} turnos pendientes o confirmados. Para reservar otro, esperá a que se realice alguno o contactá al consultorio.",
            409
        );
    }
}
