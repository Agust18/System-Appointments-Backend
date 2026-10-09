<?php

namespace App\Converter\Platform;

use App\Dto\Response\Platform\TenantCreatedResponse;
use App\Entity\Tenant\Tenant;

final class TenantToTenantCreatedResponseConverter
{
    public function convert(Tenant $tenant, bool $welcomeEmailSent): TenantCreatedResponse
    {
        return new TenantCreatedResponse(
            $tenant->getId(),
            $tenant->getSlug(),
            $tenant->getName(),
            $tenant->getStatus(),
            $tenant->getCreatedAt()?->format('Y-m-d H:i:s'),
            $tenant->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $welcomeEmailSent
        );
    }
}
