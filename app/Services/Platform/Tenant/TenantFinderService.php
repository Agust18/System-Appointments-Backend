<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantToTenantResponseConverter;
use App\Dto\Response\Platform\TenantResponse;
use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotFoundException;
use App\Models\Tenant\TenantModel;

final class TenantFinderService
{
    private TenantModel $tenantModel;
    private TenantToTenantResponseConverter $converter;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->converter = new TenantToTenantResponseConverter();
    }

    public function find(int $id): Tenant
    {
        $tenant = $this->tenantModel->find($id);

        if ($tenant === null) {
            throw new TenantNotFoundException();
        }

        return $tenant;
    }

    public function findResponse(int $id): TenantResponse
    {
        return $this->converter->convert($this->find($id));
    }
}
