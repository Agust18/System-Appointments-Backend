<?php

namespace App\Filters\Support;

use CodeIgniter\HTTP\RequestInterface;

trait ResolvesEntityByRouteSegment
{
    /**
     * Resuelve una entidad según la ruta sea /recurso/user/{userId} o /recurso/{id}.
     * Devuelve [entidad|null, $byUserId] para que el Filter sepa qué forma de ruta se usó.
     */
    private function resolveEntityByRouteSegment(
        RequestInterface $request,
        callable $findByUserId,
        callable $findById
    ): array {
        $segments = $request->getUri()->getSegments();
        $byUserId = ($segments[1] ?? null) === 'user';

        $entity = $byUserId
            ? $findByUserId((int) ($segments[2] ?? 0))
            : $findById((int) ($segments[1] ?? 0));

        return [$entity, $byUserId];
    }
}
