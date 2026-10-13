<?php

namespace App\Dto\Response\Tenant;

/**
 * Configuración PÚBLICA del Tenant (la lee el frontend sin login). Solo campos seguros (identidad):
 * los emails y la URL del frontend son configuración interna y no van acá.
 */
final readonly class TenantConfigResponse
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}
}
