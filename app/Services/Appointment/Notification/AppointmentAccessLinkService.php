<?php

namespace App\Services\Appointment\Notification;

use App\Services\Tenant\TenantEmailSettingsResolverService;

/**
 * Link `{frontend_url}/consulta?t={token}` del Tenant. Si el Tenant no tiene `frontend_url` devuelve null:
 * el mail sale igual, solo con el código.
 */
final class AppointmentAccessLinkService
{
    private TenantEmailSettingsResolverService $settingsResolver;

    public function __construct()
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
    }

    public function url(string $token): ?string
    {
        try {
            return $this->settingsResolver->frontendUrl() . '/consulta?t=' . $token;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
