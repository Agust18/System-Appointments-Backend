<?php
namespace App\Entity\Evolution\Projection;

use App\Entity\Evolution\Evolution;

/** Solo lectura: una evolución con los nombres del médico que la firma y los datos del turno. Nunca se persiste. */
final class EvolutionProjection
{
    public function __construct(
        private Evolution $evolution,
        private string $medicalName,
        private string $appointmentDate,
        private string $appointmentTime,
        private string $serviceName
    ) {
    }

    public function getEvolution(): Evolution
    {
        return $this->evolution;
    }

    public function getMedicalName(): string
    {
        return $this->medicalName;
    }

    public function getAppointmentDate(): string
    {
        return $this->appointmentDate;
    }

    public function getAppointmentTime(): string
    {
        return $this->appointmentTime;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
