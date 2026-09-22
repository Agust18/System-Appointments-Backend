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

        if (!in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "No tenés permiso para crear usuarios"
            ]);
        }

        $expectedRole = $this->expectedRole($arguments);

        if ($roleId === Role::ADMIN && $expectedRole === null) {
            return $request;
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
        $requestedRole = $body->roleId ??
            $body->role_id ??
            $body->user->roleId ??
            null;

        if ($requestedRole === null) {
            return $response->setStatusCode(400)->setJSON([
                "message" => "El campo roleId es requerido"
            ]);
        }

        $requestedRole = (int) $requestedRole;

        if ($expectedRole !== null && $requestedRole !== $expectedRole) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "El rol no coincide con el tipo de recurso que se quiere crear"
            ]);
        }

        return $request;
    }

    private function expectedRole($arguments): ?int
    {
        $resource = is_array($arguments) ? ($arguments[0] ?? null) : null;

        return match ($resource) {
            'patient' => Role::PATIENT,
            'medical' => Role::MEDICAL,
            default => null,
        };
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {}
}
