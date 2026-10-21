<?php
namespace App\Entity\Evolution;

use App\Dto\Request\Evolution\EvolutionRequest;
use App\Dto\Request\Evolution\EvolutionUpdateRequest;
use DateTime;

final class Evolution
{
    public function __construct(
        private ?int $id,
        private int $patientId,
        private int $medicalId,
        private int $appointmentId,
        private ?string $diagnosis,
        private ?string $observations,
        private ?string $treatment,
        private ?string $indications,
        private ?int $correctsId,
        private ?DateTime $signedAt,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    /** `medicalId` sale de la sesión (el médico autenticado), nunca del body. */
    public static function fromRequest(EvolutionRequest $request, int $patientId, int $medicalId): Evolution
    {
        return new Evolution(
            null,
            $patientId,
            $medicalId,
            $request->getAppointmentId(),
            $request->getDiagnosis(),
            $request->getObservations(),
            $request->getTreatment(),
            $request->getIndications(),
            $request->getCorrectsId(),
            null,
            null,
            null
        );
    }

    /** Solo se llama sobre un borrador: lo firmado no se modifica. */
    public function modify(EvolutionUpdateRequest $request): void
    {
        if ($request->getDiagnosis() !== null) {
            $this->diagnosis = $request->getDiagnosis();
        }
        if ($request->getObservations() !== null) {
            $this->observations = $request->getObservations();
        }
        if ($request->getTreatment() !== null) {
            $this->treatment = $request->getTreatment();
        }
        if ($request->getIndications() !== null) {
            $this->indications = $request->getIndications();
        }
    }

    public function isSigned(): bool
    {
        return $this->signedAt !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getAppointmentId(): int
    {
        return $this->appointmentId;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function getTreatment(): ?string
    {
        return $this->treatment;
    }

    public function getIndications(): ?string
    {
        return $this->indications;
    }

    public function getCorrectsId(): ?int
    {
        return $this->correctsId;
    }

    public function getSignedAt(): ?DateTime
    {
        return $this->signedAt;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
