<?php

namespace App\Dto\Response\Platform;

/**
 * Respuesta del alta de un Tenant: sus datos más si salió el email de bienvenida al primer ADMIN
 * (el alta no se deshace si el email falla; el panel avisa y el ADMIN puede usar forgot-password).
 */
final readonly class TenantCreatedResponse
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt,
        public bool $welcomeEmailSent,
    ) {}
}
