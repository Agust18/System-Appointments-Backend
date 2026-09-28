<?php

namespace App\Filters;

use App\Constants\Role;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ReceptionUserCreate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');

        if (!in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return \Config\Services::response()->setStatusCode(403)->setJSON([
                "message" => "No tenés permiso para crear usuarios"
            ]);
        }

        return $request;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {}
}
