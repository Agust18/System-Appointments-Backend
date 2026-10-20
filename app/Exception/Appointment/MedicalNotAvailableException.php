<?php
namespace App\Exception\Appointment;

use App\Exception\HasErrorCode;
use Exception;

/**
 * El usuario del médico (users.is_active) está inactivo.
 */
class MedicalNotAvailableException extends Exception implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('El profesional seleccionado no está disponible para recibir turnos.', 409);
    }

    public function errorCode(): string
    {
        return 'MEDICAL_NOT_AVAILABLE';
    }
}
