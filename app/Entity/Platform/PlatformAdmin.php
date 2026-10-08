<?php

namespace App\Entity\Platform;

use DateTime;

/**
 * Administrador de la plataforma (SuperAdmin). No pertenece a ningún Tenant.
 */
final class PlatformAdmin
{
    public function __construct(
        private ?int $id,
        private string $email,
        private string $firstName,
        private string $lastName,
        private string $password,
        private bool $isActive,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}

    /**
     * Alta desde el comando `platform:create-admin`: recibe la contraseña en claro y la guarda hasheada.
     */
    public static function create(string $email, string $firstName, string $lastName, string $plainPassword): self
    {
        return new self(
            null,
            $email,
            $firstName,
            $lastName,
            password_hash($plainPassword, PASSWORD_BCRYPT),
            true,
            null,
            null
        );
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
