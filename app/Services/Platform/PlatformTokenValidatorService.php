<?php

namespace App\Services\Platform;

use App\Entity\Platform\PlatformAdmin;
use App\Models\Platform\PlatformAdminModel;

final class PlatformTokenValidatorService
{
    private PlatformAdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new PlatformAdminModel();
    }

    /**
     * El platform_admin dueño del token, o null si está vacío, no existe o venció.
     * Devuelve también al admin desactivado: decidir el 403 es trabajo del filtro.
     */
    public function validate(string $token): ?PlatformAdmin
    {
        if (trim($token) === '') {
            return null;
        }

        return $this->adminModel->findByToken($token);
    }
}
