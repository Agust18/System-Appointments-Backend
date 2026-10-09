<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * El usuario del médico (users.is_active) está inactivo.
 */
class MedicalNotAvailableException extends Exception
{
    public function __construct()
    {
        parent::__construct('El profesional seleccionado no está disponible para recibir turnos.', 409);
    }
}
