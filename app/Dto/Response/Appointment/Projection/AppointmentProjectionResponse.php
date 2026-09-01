<?php

namespace App\Dto\Response\Appointment\Projection; 

final readonly class AppointmentProjectionResponse
{
    public function __construct(
        // IDs (para operaciones)
        public int $id,
        public int $medicalId,
        public int $patientId,
        
        // Projections (para mostrar)
        public string $medicoNombre,
        public string $especialidad,
        public string $pacienteNombre,
        public ?string $pacienteTelefono,
        public ?string $pacienteDni,
        
        // Datos del turno
        public string $fecha,
        public string $hora,
        public ?string $nota,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}