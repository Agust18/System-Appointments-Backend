<?php
namespace App\Exception\Appointment;

use Exception;

class MedicalServiceNotOfferedException extends Exception
{
    public function __construct(string $message = 'El profesional seleccionado no ofrece el servicio indicado o se encuentra inactivo.')
    {
        parent::__construct($message, 404);
    }
}
