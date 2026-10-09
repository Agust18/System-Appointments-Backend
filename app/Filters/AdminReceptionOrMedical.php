<?php

namespace App\Filters;

use App\Constants\Role;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminReceptionOrMedical implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $response = \Config\Services::response();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION, Role::MEDICAL], true)) {
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            'message' => 'No tenés permiso para realizar esta acción'
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}
