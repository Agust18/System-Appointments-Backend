<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantSettingsToTenantSettingsResponseConverter;
use App\Dto\Request\Platform\TenantSettingsUpdateRequest;
use App\Dto\Response\Platform\TenantSettingsResponse;
use App\Exception\Tenant\TenantSettingsNotFoundException;
use App\Models\Tenant\TenantSettingsModel;
use App\Services\Platform\Tenant\Validations\TenantSettingsUpdateValidatorService;

/**
 * Edita la configuración de un Tenant desde el panel. Funciona también con un Tenant suspendido.
 */
final class TenantSettingsUpdaterService
{
    private TenantSettingsModel $tenantSettingsModel;
    private TenantSettingsFinderService $finderService;
    private TenantSettingsUpdateValidatorService $validator;
    private TenantSettingsToTenantSettingsResponseConverter $converter;

    public function __construct()
    {
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->finderService = new TenantSettingsFinderService();
        $this->validator = new TenantSettingsUpdateValidatorService();
        $this->converter = new TenantSettingsToTenantSettingsResponseConverter();
    }

    public function update(TenantSettingsUpdateRequest $request, int $tenantId): TenantSettingsResponse
    {
        $settings = $this->finderService->find($tenantId);

        $this->validator->validate($request);

        $settings->modify($request);

        $updated = $this->tenantSettingsModel->update($settings) ?? throw new TenantSettingsNotFoundException();

        return $this->converter->convert($updated);
    }
}
