<?php
namespace App\Converter\Tenant;

use App\Dto\Response\Tenant\TenantConfigResponse;
use App\Entity\Tenant\Tenant;
use App\Entity\Tenant\TenantSettings;

final class TenantSettingsToTenantConfigResponseConverter
{
    public function convert(Tenant $tenant, TenantSettings $settings): TenantConfigResponse
    {
        return new TenantConfigResponse($tenant->getSlug(), $settings->getName(), $settings->getPrimaryColor(), $settings->getSecondaryColor());
    }
}
