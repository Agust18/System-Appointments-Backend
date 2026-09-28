<?php
namespace App\Exception\Appointment;

use Exception;

class InvalidInitialStatusException extends Exception
{
    public function __construct(string $rol)
    {
        parent::__construct("Los {$rol} solo pueden crear turnos con estado \"Pendiente\"", 403);
    }
}
