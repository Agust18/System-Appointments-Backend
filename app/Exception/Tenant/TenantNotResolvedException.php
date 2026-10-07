<?php

namespace App\Exception\Tenant;

use Exception;

/**
 * Error de programación: algo consultó el TenantContext sin que el filtro 'tenant' lo completara.
 * Falla ruidosamente en lugar de consultar sin filtro de clínica.
 */
class TenantNotResolvedException extends Exception
{
    public function __construct()
    {
        parent::__construct("No se resolvio el cliente de la solicitud", 500);
    }
}
