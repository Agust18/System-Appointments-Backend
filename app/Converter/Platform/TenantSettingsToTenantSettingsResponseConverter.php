<?php

namespace App\Converter\Platform;

use App\Dto\Response\Platform\TenantSettingsResponse;
use App\Entity\Tenant\TenantSettings;

final class TenantSettingsToTenantSettingsResponseConverter
{
    public function convert(TenantSettings $settings): TenantSettingsResponse
    {
        return new TenantSettingsResponse(
            $settings->getTenantId(),
            $settings->getName(),
            $settings->getReplyToEmail(),
            $settings->getFrontendUrl(),
            $settings->getReceptionEmail(),
            $settings->isReceptionEmailEnabled(),
            $settings->getContactPhone(),
            $settings->getContactWhatsapp(),
            $settings->getContactEmail(),
            $settings->getAddress(),
            $settings->getOpeningHours(),
            $settings->isLandingEnabled(),
            $settings->getCreatedAt()?->format('Y-m-d H:i:s'),
            $settings->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
