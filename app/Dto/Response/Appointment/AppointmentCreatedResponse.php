<?php
namespace App\Dto\Response\Appointment;

use DateTime;

/**
 * Respuesta del alta de un turno desde el panel: lo mismo que AppointmentResponse más el código de
 * `/consulta` en claro, que recepción le pasa al paciente. Es la única vez que se muestra; si se pierde
 * se regenera con `POST /appointments/{id}/access-code`.
 */
final readonly class AppointmentCreatedResponse
{
    public function __construct(
        public int $id,
        public int $medicalId,
        public int $patientId,
        public DateTime $fecha,
        public string $hora,
        public ?string $nota,
        public string $status,
        public DateTime $createdAt,
        public ?DateTime $updatedAt,
        public string $accessCode
    ) {}
}
