<?php
namespace App\Dto\Response\Appointment;

/**
 * Confirmación de una reserva pública. Deliberadamente mínima: sin ids de usuario ni
 * de paciente, ni datos de contacto, porque quien la recibe no está autenticado.
 */
final readonly class PublicAppointmentResponse
{
    public function __construct(
        public int $id,
        public string $fecha,
        public string $hora,
        public string $medicoNombre,
        public string $servicioNombre,
        public string $status
    ) {}
}
