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
        $response = \Config\Services::response();

        // ADMIN puede crear cualquier tipo de usuario
        if ($roleId === Role::ADMIN) {
            return $request;
        }

        // Solo RECEPCIÓN tiene permisos limitados
        if ($roleId !== Role::RECEPTION) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "No tenés permiso para crear usuarios"
            ]);
        }

        // Obtener el body JSON
        $rawBody = file_get_contents('php://input');
        $body = json_decode($rawBody);

        if (!is_object($body)) {
            return $response->setStatusCode(400)->setJSON([
                "message" => "JSON inválido o mal formado"
            ]);
        }

        // Obtener el rol solicitado
        $requestedRole = $body->roleId ?? $body->role_id ?? null;

        if ($requestedRole === null) {
            return $response->setStatusCode(400)->setJSON([
                "message" => "El campo roleId es requerido"
            ]);
        }

        $requestedRole = (int) $requestedRole;

        // RECEPCIÓN solamente puede crear PACIENTES o MÉDICOS
        if (!in_array($requestedRole, [
            Role::PATIENT,
            Role::MEDICAL
        ], true)) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "Solo podés crear usuarios con rol paciente o médico"
            ]);
        }

        return $request;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}