<?php

namespace App\Filters;

use App\Constants\Role;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminOnly implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $response = \Config\Services::response();
        if ($roleId === Role::ADMIN) {
            return $request;
        }
        return $response->setStatusCode(403)->setJSON([
            "message" => "Solo un administrador puede realizar esta acción"
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
