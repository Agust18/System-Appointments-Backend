<?php

namespace App\Entity\UserToken;

use DateTime;

final class UserToken
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private string $token,
        private string $type,
        private DateTime $expiresAt,
        private ?DateTime $usedAt,
        private DateTime $createdAt,
    ) {
    }

    public function isExpired(): bool
    {
        return new DateTime() > $this->expiresAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    public function markAsUsed(): void
    {
        $this->usedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?DateTime
    {
        return $this->usedAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}