<?php

namespace App\Dto\Response\Appointment\Projection;

final readonly class AppointmentProjectionResponse
{
    public function __construct(
        // IDs (para operaciones)
        public int $id,
        public int $medicalId,
        public int $patientId,
        public int $serviceId,

        // Projections (para mostrar)
        public string $medicoNombre,
        public string $pacienteNombre,
        public ?string $servicioNombre,
        public ?string $paciente_email,


        // Datos del turno
        public string $fecha,
        public string $hora,
        public ?string $nota,
        public string $status

    ) {}
}
