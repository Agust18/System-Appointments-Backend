<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantToTenantResponseConverter;
use App\Dto\Request\Platform\TenantUpdateRequest;
use App\Dto\Response\Platform\TenantResponse;
use App\Models\Tenant\TenantModel;

/**
 * Cambia el nombre y el estado de un Tenant. Suspender y reactivar son el mismo PUT: los datos
 * del Tenant se conservan; el filtro `tenant` es quien corta sus rutas con 403.
 */
final class TenantUpdaterService
{
    private TenantModel $tenantModel;
    private TenantFinderService $finderService;
    private TenantToTenantResponseConverter $converter;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->finderService = new TenantFinderService();
        $this->converter = new TenantToTenantResponseConverter();
    }

    public function update(TenantUpdateRequest $request, int $id): TenantResponse
    {
        $tenant = $this->finderService->find($id);

        $tenant->modify($request);

        return $this->converter->convert($this->tenantModel->update($tenant));
    }
}
