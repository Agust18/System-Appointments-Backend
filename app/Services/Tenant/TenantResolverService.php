<?php

namespace App\Services\Tenant;

use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotFoundException;
use App\Exception\Tenant\TenantSuspendedException;
use App\Models\Tenant\TenantModel;

final class TenantResolverService
{
    private const SLUG_PATTERN = '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/';

    private TenantModel $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    /**
     * @throws TenantNotFoundException  slug con formato inválido o inexistente (no se distingue)
     * @throws TenantSuspendedException
     */
    public function resolve(string $slug): Tenant
    {
        $slug = strtolower(trim($slug));

        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            throw new TenantNotFoundException();
        }

        $tenant = $this->tenantModel->findBySlug($slug);

        if ($tenant === null) {
            throw new TenantNotFoundException();
        }

        if ($tenant->isSuspended()) {
            throw new TenantSuspendedException();
        }

        return $tenant;
    }
}
