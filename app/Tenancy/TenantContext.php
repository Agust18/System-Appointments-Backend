<?php

namespace App\Tenancy;

use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotResolvedException;

/**
 * Tenant de la request actual. La completa el filtro 'tenant' una sola vez; los Models la
 * leen para agregar `AND tenant_id = ?`. Si nadie la completó, falla en lugar de devolver null:
 * una consulta sin filtro de tenant expondría datos entre Tenants.
 */
final class TenantContext
{
    private static ?Tenant $tenant = null;

    public static function set(Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function id(): int
    {
        return self::tenant()->getId();
    }

    public static function tenant(): Tenant
    {
        return self::$tenant ?? throw new TenantNotResolvedException();
    }

    /**
     * El Tenant actual o null. Para los procesos que cambian de Tenant (recordatorios, limpieza)
     * y necesitan restaurar el contexto previo; el código de negocio debe usar id() o tenant().
     */
    public static function current(): ?Tenant
    {
        return self::$tenant;
    }

    public static function isSet(): bool
    {
        return self::$tenant !== null;
    }

    public static function clear(): void
    {
        self::$tenant = null;
    }
}
