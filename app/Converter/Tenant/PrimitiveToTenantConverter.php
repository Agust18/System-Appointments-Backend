<?php
namespace App\Converter\Tenant;

use App\Entity\Tenant\Tenant;
use DateTime;

final class PrimitiveToTenantConverter
{
    public function convert(object $primitive): Tenant
    {
        return new Tenant(
            (int) $primitive->id,
            $primitive->slug,
            $primitive->name,
            $primitive->status,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
