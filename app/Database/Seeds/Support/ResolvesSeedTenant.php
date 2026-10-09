<?php

namespace App\Database\Seeds\Support;

use App\Models\Tenant\TenantModel;
use App\Tenancy\TenantContext;
use RuntimeException;

/**
 * Los seeders no dependen del DEFAULT de tenant_id: exigen un Tenant explícito, `seed.tenantSlug`
 * en el .env. Si falta o no existe, abortan sin escribir nada. Fijan el TenantContext mientras
 * corren y restauran el que hubiera antes.
 */
trait ResolvesSeedTenant
{
    /**
     * @param callable(int): void $callback recibe el id del Tenant sembrado
     */
    private function withSeedTenant(callable $callback): void
    {
        $slug = strtolower(trim((string) env('seed.tenantSlug', '')));

        if ($slug === '') {
            throw new RuntimeException(
                'Falta seed.tenantSlug en el .env: el seeder no siembra ni borra sin un Tenant explícito.'
            );
        }

        $tenant = (new TenantModel())->findBySlug($slug);

        if ($tenant === null) {
            throw new RuntimeException("seed.tenantSlug apunta a un Tenant que no existe: '{$slug}'.");
        }

        $previous = TenantContext::current();
        TenantContext::set($tenant);

        try {
            echo "Tenant: {$tenant->getSlug()} (id {$tenant->getId()})\n";
            $callback($tenant->getId());
        } finally {
            $previous !== null ? TenantContext::set($previous) : TenantContext::clear();
        }
    }
}
