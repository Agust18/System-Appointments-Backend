<?php
namespace App\Entity\Appointment;

/**
 * Credenciales de `/consulta` de un turno, en claro. Existen solo en el momento de emitirlas:
 * en la base se guarda únicamente su hash.
 */
final readonly class AppointmentAccess
{
    public function __construct(
        public string $code,
        public string $token
    ) {}
}
