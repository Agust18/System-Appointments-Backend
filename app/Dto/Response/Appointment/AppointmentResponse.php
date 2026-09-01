<?php
namespace App\Dto\Response\Appointment;

final readonly class AppointmentResponse
{
    public function __construct(
        public int $id,
        public int $medicalId,
        public int $patientId,
        public string $fecha,
        public string $hora,
        public ?string $nota,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}