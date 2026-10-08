<?php

namespace App\Exception\Tenant;

use Exception;

/**
 * Todo Tenant debe tener su fila de settings (la crea el alta y el backfill de la migración):
 * que falte es una inconsistencia, no un "no encontrado" del cliente.
 */
class TenantSettingsNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("El cliente no tiene configuración", 500);
    }
}
