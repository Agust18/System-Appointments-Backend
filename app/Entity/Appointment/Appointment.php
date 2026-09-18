<?php
namespace App\Entity\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use DateTime;

final class Appointment
{
    public function __construct(
        private ?int $id,
        private int $medicalId,
        private int $patientId,
        private int $serviceId,
        private DateTime $fecha,
        private DateTime $hora,
        private ?DateTime $horaFin,
        private ?string $nota,
        private string $status,
        private DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    /**
     * Aplica los cambios "de negocio" que vienen del usuario. NO toca
     * horaFin: ese campo nunca lo decide el usuario, se recalcula aparte
     * con setHoraFin() dentro de AppointmentUpdateBookingService, bajo lock.
     */
    public function modify(AppointmentUpdateRequest $request): void
    {
        $this->medicalId = $request->getMedicalId() ?? $this->medicalId;
        $this->patientId = $request->getPatientId() ?? $this->patientId;
        $this->serviceId = $request->getServiceId() ?? $this->serviceId;
        $this->nota = $request->getNota() ?? $this->nota;
        $this->status = $request->getStatus() ?? $this->status;

        if ($request->getFecha() !== null) {
            $this->fecha = new DateTime($request->getFecha());
        }
        if ($request->getHora() !== null) {
            $this->hora = new DateTime($request->getHora());
        }

        $this->updatedAt = new DateTime();
    }

    /**
     * Setter dedicado para horaFin: se calcula a partir del servicio
     * vigente (duración + buffer), nunca se recibe directo del usuario.
     */
    public function setHoraFin(DateTime $horaFin): void
    {
        $this->horaFin = $horaFin;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getHora(): DateTime
    {
        return $this->hora;
    }

    public function getHoraFin(): ?DateTime
    {
        return $this->horaFin;
    }

    public function getNota(): ?string
    {
        return $this->nota;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function fromRequest(AppointmentRequest $request): Appointment
    {
        return new Appointment(
            null,
            $request->getMedicalId(),
            $request->getPatientId(),
            $request->getServiceId(),
            new DateTime($request->getFecha()),
            new DateTime($request->getHora()),
            null,
            $request->getNota(),
            $request->getStatus(),
            new DateTime(),
            null
        );
    }
}