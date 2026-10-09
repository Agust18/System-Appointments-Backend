<?php

namespace App\Services\Platform\Tenant;

use App\Converter\Platform\TenantToTenantResponseConverter;
use App\Dto\Request\Platform\TenantFilterRequest;
use App\Dto\Response\Platform\TenantResponse;
use App\Models\Tenant\TenantModel;

final class TenantsSearcherService
{
    private TenantModel $tenantModel;
    private TenantToTenantResponseConverter $converter;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->converter = new TenantToTenantResponseConverter();
    }

    /**
     * @return array{data: TenantResponse[], total: int}
     */
    public function searchResponses(TenantFilterRequest $request): array
    {
        $responses = [];
        foreach ($this->tenantModel->search($request) as $tenant) {
            $responses[] = $this->converter->convert($tenant);
        }

        return [
            'data' => $responses,
            'total' => $this->tenantModel->countByFilter($request),
        ];
    }
}
