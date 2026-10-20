<?php
namespace App\Entity\Patient;

use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\Patient\PatientUpdateRequest;
use DateTime;

final class Patient
{
    public function __construct(
        private ?int $id,
        private string $firstName,
        private string $lastName,
        private ?string $email,
        private int $dni,
        private ?string $phone,
        private ?DateTime $birthDate,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {
    }

    public function modify(PatientUpdateRequest $request): void
    {
        if ($request->getFirstName() !== null) {
            $this->firstName = $request->getFirstName();
        }
        if ($request->getLastName() !== null) {
            $this->lastName = $request->getLastName();
        }
        if ($request->getEmail() !== null) {
            $this->email = $request->getEmail();
        }
        if ($request->getPhone() !== null) {
            $this->phone = $request->getPhone();
        }
        if ($request->getBirthDate() !== null) {
            $this->birthDate = new DateTime($request->getBirthDate());
        }
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function hasEmail(): bool
    {
        return $this->email !== null && $this->email !== '';
    }

    public function getDni(): int
    {
        return $this->dni;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
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

    public static function fromRequest(PatientRequest $request): Patient
    {
        return new Patient(
            null,
            $request->getFirstName(),
            $request->getLastName(),
            $request->getEmail(),
            $request->getDni(),
            $request->getPhone(),
            $request->getBirthDate() ? new DateTime($request->getBirthDate()) : null,
            new DateTime(),
            new DateTime()
        );
    }
}
