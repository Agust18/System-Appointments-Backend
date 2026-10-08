<?php

namespace App\Converter\Platform;

use App\Dto\Response\Platform\TenantResponse;
use App\Entity\Tenant\Tenant;

final class TenantToTenantResponseConverter
{
    public function convert(Tenant $tenant): TenantResponse
    {
        return new TenantResponse(
            $tenant->getId(),
            $tenant->getSlug(),
            $tenant->getName(),
            $tenant->getStatus(),
            $tenant->getCreatedAt()?->format('Y-m-d H:i:s'),
            $tenant->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
