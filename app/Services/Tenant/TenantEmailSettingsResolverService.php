<?php

namespace App\Services\Tenant;

use App\Entity\Tenant\TenantSettings;
use App\Exception\Tenant\TenantFrontendUrlNotConfiguredException;
use App\Models\Tenant\TenantSettingsModel;
use App\Tenancy\TenantContext;

/**
 * Valores de email del Tenant del TenantContext, leídos de `tenant_settings`. Lee la fila una vez
 * por instancia: quien cambie de Tenant (recordatorios) debe crear una instancia por Tenant.
 *
 * Sin fila de settings, solo frontendUrl() falla (rompe el link); lo demás cae a "sin configurar".
 */
final class TenantEmailSettingsResolverService
{
    private TenantSettingsModel $model;
    private ?TenantSettings $settings = null;
    private bool $loaded = false;

    public function __construct(?TenantSettingsModel $model = null)
    {
        $this->model = $model ?? new TenantSettingsModel();
    }

    /**
     * Origen del frontend del Tenant, normalizado y sin `/` final. No hay respaldo en el .env.
     *
     * @throws TenantFrontendUrlNotConfiguredException
     */
    public function frontendUrl(): string
    {
        $url = $this->nullIfBlank($this->settings()?->getFrontendUrl());

        if ($url === null) {
            log_message('error', 'El Tenant ' . TenantContext::tenant()->getSlug() . ' no tiene frontend_url configurado.');
            throw new TenantFrontendUrlNotConfiguredException();
        }

        return rtrim($url, '/');
    }

    /**
     * Falla antes de escribir nada, para los flujos que envían un link después de crear datos.
     *
     * @throws TenantFrontendUrlNotConfiguredException
     */
    public function assertFrontendUrlConfigured(): void
    {
        $this->frontendUrl();
    }

    /**
     * reply_to_email → reception_email → null (sin header: el cliente responde al From).
     * reception_email cuenta aunque el aviso a recepción esté desactivado.
     */
    public function replyTo(): ?string
    {
        $settings = $this->settings();

        return $this->nullIfBlank($settings?->getReplyToEmail())
            ?? $this->nullIfBlank($settings?->getReceptionEmail());
    }

    public function receptionEmail(): ?string
    {
        return $this->nullIfBlank($this->settings()?->getReceptionEmail());
    }

    public function isReceptionEmailEnabled(): bool
    {
        return $this->settings()?->isReceptionEmailEnabled() ?? true;
    }

    private function settings(): ?TenantSettings
    {
        if (!$this->loaded) {
            $this->settings = $this->model->find();
            $this->loaded   = true;
        }

        return $this->settings;
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : $value;
    }
}
