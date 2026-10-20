<?php
namespace App\Exception\Appointment;

use App\Exception\HasErrorCode;
use Exception;

class AppointmentAlreadyExistsException extends Exception implements HasErrorCode
{
    public function errorCode(): string
    {
        return 'SLOT_TAKEN';
    }

    public function __construct()
    {
        parent::__construct(
            'El horario seleccionado ya no se encuentra disponible. Por favor, elija otro horario.',
            409
        );
    }
}