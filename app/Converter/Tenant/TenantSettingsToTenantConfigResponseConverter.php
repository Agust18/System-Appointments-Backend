<?php
namespace App\Converter\Tenant;

use App\Dto\Response\Tenant\TenantConfigResponse;
use App\Dto\Response\Tenant\TenantContactResponse;
use App\Entity\Tenant\Tenant;
use App\Entity\Tenant\TenantSettings;

final class TenantSettingsToTenantConfigResponseConverter
{
    public function convert(Tenant $tenant, TenantSettings $settings): TenantConfigResponse
    {
        return new TenantConfigResponse(
            $tenant->getSlug(),
            $settings->getName(),
            $settings->isLandingEnabled(),
            new TenantContactResponse(
                $settings->getContactPhone(),
                $settings->getContactWhatsapp(),
                $settings->getContactEmail(),
                $settings->getAddress(),
                $settings->getOpeningHours()
            )
        );
    }
}
