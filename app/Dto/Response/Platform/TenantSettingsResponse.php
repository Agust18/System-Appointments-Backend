<?php

namespace App\Dto\Response\Platform;

/**
 * Configuración completa de un Tenant. Solo para el panel de plataforma: incluye datos internos
 * (emails, URL del frontend) que GET /tenant/config no publica.
 */
final readonly class TenantSettingsResponse
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $replyToEmail,
        public ?string $frontendUrl,
        public ?string $receptionEmail,
        public bool $receptionEmailEnabled,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}
}
