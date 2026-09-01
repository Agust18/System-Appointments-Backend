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

        //Admin, Recepcionista y Paciente pueden crear turnos
        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION, Role::PATIENT], true)) {
            return $request;
        }

        //Médico NO puede crear turnos
        if ($roleId === Role::MEDICAL) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "Los médicos no pueden crear turnos. Solo pueden visualizarlos y cambiar su estado."
            ]);
        }

        return $response->setStatusCode(403)->setJSON([
            "message" => "No tenés permiso para crear turnos"
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}