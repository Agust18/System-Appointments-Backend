<?php

namespace App\Entity\Platform;

use DateTime;

/**
 * Token de sesión de un platform_admin. Se emite en el login y vive en `platform_admin_tokens`.
 */
final class PlatformAdminToken
{
    private const TTL = '+24 hours';

    private function __construct(
        private int $platformAdminId,
        private string $token,
        private DateTime $expiresAt
    ) {}

    public static function issueFor(int $platformAdminId): self
    {
        return new self($platformAdminId, bin2hex(random_bytes(32)), new DateTime(self::TTL));
    }

    public function getPlatformAdminId(): int
    {
        return $this->platformAdminId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }
}
