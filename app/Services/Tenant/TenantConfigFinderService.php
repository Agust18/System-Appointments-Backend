<?php

namespace App\Services\Tenant;

use App\Converter\Tenant\TenantSettingsToTenantConfigResponseConverter;
use App\Dto\Response\Tenant\TenantConfigResponse;
use App\Entity\Tenant\TenantSettings;
use App\Exception\Tenant\TenantSettingsNotFoundException;
use App\Models\Tenant\TenantSettingsModel;
use App\Tenancy\TenantContext;

final class TenantConfigFinderService
{
    private TenantSettingsModel $tenantSettingsModel;
    private TenantSettingsToTenantConfigResponseConverter $converter;

    public function __construct()
    {
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->converter = new TenantSettingsToTenantConfigResponseConverter();
    }

    /**
     * La configuración del Tenant del contexto actual. Lee del contexto, no recibe parámetro.
     */
    public function find(): TenantSettings
    {
        $settings = $this->tenantSettingsModel->find();

        if (!$settings) {
            throw new TenantSettingsNotFoundException();
        }

        return $settings;
    }

    public function findResponse(): TenantConfigResponse
    {
        return $this->converter->convert(TenantContext::tenant(), $this->find());
    }
}
