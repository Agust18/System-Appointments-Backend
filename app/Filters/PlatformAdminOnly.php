<?php

namespace App\Filters;

use App\Services\Platform\PlatformTokenValidatorService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autentica y autoriza a un platform_admin en /platform/*. REEMPLAZA a `auth` en esas rutas:
 * `Auth` resuelve el token contra `users` filtrando por Tenant, y /platform/* no tiene Tenant.
 *
 *  - Sin token, token inválido, vencido o de un usuario de Tenant: 401 (no se distingue, para no
 *    filtrar información).
 *  - platform_admin desactivado: 403.
 *
 * Solo guarda `platformAdminId` en sesión; nunca `userId` ni `roleId`, que son de los usuarios
 * de Tenant. Devuelve un Response (no lanza excepción), como los demás Filters.
 */
class PlatformAdminOnly implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $response = \Config\Services::response();

        if (empty($token)) {
            return $response->setStatusCode(401)->setJSON([
                'message' => 'Token no encontrado',
            ]);
        }

        $admin = (new PlatformTokenValidatorService())->validate($token);

        if ($admin === null) {
            return $response->setStatusCode(401)->setJSON([
                'message' => 'Token invalido o expirado',
            ]);
        }

        if (!$admin->isActive()) {
            return $response->setStatusCode(403)->setJSON([
                'message' => 'El administrador de plataforma no está activo',
            ]);
        }

        session()->set('platformAdminId', $admin->getId());

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
