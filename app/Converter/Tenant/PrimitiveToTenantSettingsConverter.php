<?php
namespace App\Converter\Tenant;

use App\Entity\Tenant\TenantSettings;
use DateTime;

final class PrimitiveToTenantSettingsConverter
{
    public function convert(object $primitive): TenantSettings
    {
        return new TenantSettings(
            (int) $primitive->tenant_id,
            $primitive->name,
            $primitive->reply_to_email,
            $primitive->frontend_url,
            $primitive->reception_email,
            (bool) $primitive->reception_email_enabled,
            $primitive->primary_color,
            $primitive->secondary_color,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
