<?php

namespace App\Services\Platform\Tenant\Validations;

use App\Dto\Request\Platform\TenantRequest;
use App\Exception\Platform\TenantSlugAlreadyExistsException;
use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantModel;

final class TenantCreateValidatorService
{
    /** Subdominios que no pueden ser de un cliente: infraestructura o rutas propias de la plataforma. */
    private const RESERVED_SLUGS = [
        'www', 'api', 'app', 'platform', 'admin', 'panel', 'dashboard', 'root', 'support',
        'status', 'mail', 'smtp', 'ftp', 'static', 'assets', 'cdn', 'login', 'public',
    ];

    private TenantModel $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    public function validate(TenantRequest $request): void
    {
        if (in_array($request->getSlug(), self::RESERVED_SLUGS, true)) {
            throw new ValidationException(['slug' => 'Ese identificador está reservado.']);
        }

        if ($this->tenantModel->existsBySlug($request->getSlug())) {
            throw new TenantSlugAlreadyExistsException();
        }
    }
}
