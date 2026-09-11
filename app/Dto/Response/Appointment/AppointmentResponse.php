<?php
namespace App\Dto\Response\Appointment;

use DateTime;

final readonly class AppointmentResponse
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
        public ?DateTime $updatedAt
    ) {}
}