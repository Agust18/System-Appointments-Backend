<?php

namespace App\Filters;

use App\Constants\Role;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Deja pasar solo a los roles indicados en la ruta: `role:admin`, `role:admin,reception`.
 * Los argumentos son los nombres de Role::NAMES. Un nombre desconocido nunca coincide (falla cerrado).
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $allowedIds = array_keys(array_intersect(Role::NAMES, $arguments ?? []));

        if (in_array($roleId, $allowedIds, true)) {
            return $request;
        }

        return \Config\Services::response()->setStatusCode(403)->setJSON([
            'message' => 'No tenés permiso para realizar esta acción'
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
