<?php

namespace App\Exception\Tenant;

use Exception;

/**
 * Los emails de verificación y de reset arman su link con el `frontend_url` del Tenant. Sin él no
 * hay un link correcto posible (no se usa el .env como respaldo): falla visible en vez de mandar
 * un link roto. Se configura con PUT /platform/tenants/{id}/settings.
 */
class TenantFrontendUrlNotConfiguredException extends Exception
{
    public function __construct()
    {
        parent::__construct("El cliente no tiene configurada la URL del frontend", 500);
    }
}
