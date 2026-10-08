<?php

namespace App\Converter\Platform;

use App\Entity\Platform\PlatformAdmin;
use DateTime;

final class PrimitiveToPlatformAdminConverter
{
    public function convert(object $primitive): PlatformAdmin
    {
        return new PlatformAdmin(
            (int) $primitive->id,
            $primitive->email,
            $primitive->first_name,
            $primitive->last_name,
            $primitive->password,
            (bool) $primitive->is_active,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
