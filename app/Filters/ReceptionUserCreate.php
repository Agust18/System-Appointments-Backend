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

        // puede todo
        if ($roleId === Role::ADMIN) {
            return $request;
        }

        // Permisos limitados
        if ($roleId === Role::RECEPTION) {
            $uri = $request->getUri()->getPath();
            
           
            // El rol ya está definido por la ruta, permitir siempre
            if (strpos($uri, 'quick-create') !== false) {
                return $request;
            }
            
           
            // Validar que el rol sea paciente o médico
            $rawBody = file_get_contents('php://input');
            $body = json_decode($rawBody);
            
            if (!is_object($body)) {
                return $response->setStatusCode(400)->setJSON([
                    "message" => "JSON inválido o mal formado"
                ]);
            }
            
            $requestedRole = $body->roleId ?? $body->role_id ?? null;
            
            if ($requestedRole === null) {
                return $response->setStatusCode(400)->setJSON([
                    "message" => "El campo roleId es requerido"
                ]);
            }
            
            $requestedRole = (int) $requestedRole;
            
         
            if ($requestedRole !== Role::PATIENT && $requestedRole !== Role::MEDICAL) {
                return $response->setStatusCode(403)->setJSON([
                    "message" => "Solo podés crear usuarios con rol paciente o médico"
                ]);
            }
            
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            "message" => "No tenés permiso para crear usuarios"
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}