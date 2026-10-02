<?php
namespace App\Entity\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientUpdateRequest;
use DateTime;

final class Patient
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private ?DateTime $birthDate,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    public function modify(PatientUpdateRequest $request): void
    {
        if ($request->getBirthDate() !== null) {
            $this->birthDate = new DateTime($request->getBirthDate());
        }
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    
    public static function fromRequest(int $userId, PatientDataRequest $request): Patient
    {
        return new Patient(
            null,
            $userId,
            $request->getBirthDate() ? new DateTime($request->getBirthDate()) : null,
            new DateTime(),
            new DateTime()
        );
    }
}
