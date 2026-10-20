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
            $primitive->contact_phone,
            $primitive->contact_whatsapp,
            $primitive->contact_email,
            $primitive->address,
            $primitive->opening_hours,
            (bool) $primitive->landing_enabled,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
