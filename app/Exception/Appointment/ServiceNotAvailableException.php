<?php
namespace App\Exception\Appointment;

use Exception;

class ServiceNotAvailableException extends Exception
{
    public function __construct(string $message = 'El servicio seleccionado no existe o no se encuentra activo.')
    {
        parent::__construct($message, 404);
    }
}
