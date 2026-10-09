<?php

namespace App\Converter\Platform;

use App\Dto\Response\Platform\PlatformAdminResponse;
use App\Entity\Platform\PlatformAdmin;

final class PlatformAdminToPlatformAdminResponseConverter
{
    public function convert(PlatformAdmin $admin): PlatformAdminResponse
    {
        return new PlatformAdminResponse(
            $admin->getId(),
            $admin->getEmail(),
            $admin->getFirstName(),
            $admin->getLastName(),
            $admin->isActive()
        );
    }
}
