<?php

namespace App\Services\Platform\Tenant\Validations;

use App\Dto\Request\Platform\TenantRequest;
use App\Exception\Platform\TenantSlugAlreadyExistsException;
use App\Exception\Validation\ValidationException;
use App\Models\Tenant\TenantModel;
use App\Tenancy\FrontendUrl;

final class TenantCreateValidatorService
{
    /** Subdominios que no pueden ser de un cliente: infraestructura o rutas propias de la plataforma. */
    private const RESERVED_SLUGS = [
        'www', 'api', 'app', 'platform', 'admin', 'panel', 'dashboard', 'root', 'support',
        'status', 'mail', 'smtp', 'ftp', 'static', 'assets', 'cdn', 'login', 'public',
    ];

    private TenantModel $tenantModel;
    private bool $allowHttp;

    /** @param bool|null $allowHttp null = `http` solo fuera de producción */
    public function __construct(?bool $allowHttp = null)
    {
        $this->tenantModel = new TenantModel();
        $this->allowHttp = $allowHttp ?? ENVIRONMENT !== 'production';
    }

    public function validate(TenantRequest $request): void
    {
        if (in_array($request->getSlug(), self::RESERVED_SLUGS, true)) {
            throw new ValidationException(['slug' => 'Ese identificador está reservado.']);
        }

        if (FrontendUrl::normalize($request->getFrontendUrl(), $this->allowHttp) === null) {
            throw new ValidationException(['frontendUrl' => $this->allowHttp
                ? 'La URL del frontend debe ser http(s)://dominio[:puerto], sin ruta, parámetros ni usuario.'
                : 'La URL del frontend debe ser https://dominio[:puerto], sin ruta, parámetros ni usuario.']);
        }

        if ($this->tenantModel->existsBySlug($request->getSlug())) {
            throw new TenantSlugAlreadyExistsException();
        }
    }
}
