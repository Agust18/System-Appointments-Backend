<?php
namespace App\Dto\Response\Appointment;

/**
 * Confirmación de una reserva pública, con las credenciales de `/consulta` en claro: es la única vez
 * que se entregan (en la base solo queda su hash).
 */
final readonly class PublicAppointmentCreatedResponse
{
    public function __construct(
        public int $id,
        public string $fecha,
        public string $hora,
        public string $medicoNombre,
        public string $servicioNombre,
        public string $status,
        public string $accessCode,
        public string $accessToken
    ) {}
}
