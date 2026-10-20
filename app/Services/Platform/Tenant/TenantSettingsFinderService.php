<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantSettingsToTenantSettingsResponseConverter;
use App\Dto\Response\Platform\TenantSettingsResponse;
use App\Entity\Tenant\TenantSettings;
use App\Exception\Tenant\TenantSettingsNotFoundException;
use App\Models\Tenant\TenantSettingsModel;


final class TenantSettingsFinderService
{
    private TenantSettingsModel $tenantSettingsModel;
    private TenantFinderService $tenantFinderService;
    private TenantSettingsToTenantSettingsResponseConverter $converter;

    public function __construct()
    {
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->tenantFinderService = new TenantFinderService();
        $this->converter = new TenantSettingsToTenantSettingsResponseConverter();
    }

    public function find(int $tenantId): TenantSettings
    {
        $this->tenantFinderService->find($tenantId);

        $settings = $this->tenantSettingsModel->findByTenantId($tenantId);

        if (!$settings) {
            throw new TenantSettingsNotFoundException();
        }

        return $settings;
    }

    public function findResponse(int $tenantId): TenantSettingsResponse
    {
        return $this->converter->convert($this->find($tenantId));
    }
}
