<?php

namespace App\Filters;

use App\Exception\Tenant\TenantNotFoundException;
use App\Exception\Tenant\TenantSuspendedException;
use App\Services\Tenant\TenantResolverService;
use App\Tenancy\TenantContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Lee el header `X-Tenant` (slug del Tenant), carga el Tenant y completa el TenantContext.
 * Corta con 400 si falta el header, 404 si el Tenant no existe y 403 si está suspendido
 * (devuelve el Response, no lanza excepción, como los demás Filters).
 *
 * Va como filtro global. Los preflight OPTIONS pasan sin Tenant: el navegador no manda
 * headers propios en el preflight y los responde el filtro 'cors'.
 *
 * FALLBACK TRANSITORIO: si falta el header y el entorno NO es producción, se usa el slug de
 * `tenant.fallbackSlug` (.env). En producción nunca aplica. Se elimina cuando el frontend
 * mande X-Tenant (ver bitacora/pendiente/multitenant-plan.md).
 */
final class TenantResolver implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $slug = trim($request->getHeaderLine('X-Tenant'));

        if ($slug === '') {
            $slug = $this->fallbackSlug();
        }

        $response = service('response');

        if ($slug === '') {
            return $response->setStatusCode(400)->setJSON([
                'message' => 'Falta el header X-Tenant',
            ]);
        }

        try {
            $tenant = (new TenantResolverService())->resolve($slug);
        } catch (TenantNotFoundException | TenantSuspendedException $e) {
            return $response->setStatusCode($e->getCode())->setJSON([
                'message' => $e->getMessage(),
            ]);
        }

        TenantContext::set($tenant);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function fallbackSlug(): string
    {
        if (ENVIRONMENT === 'production') {
            return '';
        }

        return trim((string) env('tenant.fallbackSlug', ''));
    }
}
