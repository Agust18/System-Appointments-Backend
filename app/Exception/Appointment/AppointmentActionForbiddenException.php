<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * El rol del usuario no puede hacer esta modificación sobre el turno
 * (un médico cambiando campos que no son estado/nota, un paciente que no cancela, etc.).
 */
class AppointmentActionForbiddenException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 403);
    }

    public static function medicalCanOnlyChangeStatus(): self
    {
        return new self('Los médicos solo pueden modificar el estado y la nota del turno');
    }

    public static function medicalCanOnlyComplete(): self
    {
        return new self('Los médicos solo pueden completar o cancelar el turno');
    }

    public static function patientCanOnlyCancel(): self
    {
        return new self('Los pacientes solo pueden cancelar turnos');
    }
}
