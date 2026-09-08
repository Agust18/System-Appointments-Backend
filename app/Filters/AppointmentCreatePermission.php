<?php

namespace App\Filters;

use App\Constants\Role;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AppointmentCreatePermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $response = \Config\Services::response();

        // Admin, Recepcionista y Paciente pueden crear turnos
        $allowedRoles = [
            Role::ADMIN,
            Role::RECEPTION,
            Role::PATIENT,
        ];

        if (in_array($roleId, $allowedRoles, true)) {
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            "message" => "No tenés permiso para crear turnos"
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}