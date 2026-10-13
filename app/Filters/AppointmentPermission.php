<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Services\Permissions\PermissionsService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AppointmentPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');

        $response = \Config\Services::response();

        $method = strtolower($request->getMethod());

        // ADMIN y RECEPCIÓN pueden modificar/eliminar cualquier turno
        if (in_array($roleId, [
            Role::ADMIN,
            Role::RECEPTION
        ], true)) {
            return $request;
        }

        // Obtener ID del turno
        $appointmentId = (int) $request->getUri()->getSegment(2);

        $appointmentModel = new AppointmentModel();
        $appointment = $appointmentModel->find($appointmentId);

        // Verificar que el turno exista
        if (!$appointment) {
            return $response->setStatusCode(404)->setJSON([
                "message" => "Turno no encontrado"
            ]);
        }

        $permissionsService = new PermissionsService();

        // El paciente solo puede cancelar su propio turno confirmado.
        if ($roleId === Role::PATIENT) {
            if ($method === 'put' && $permissionsService->canCancelAppointment($roleId, $userId, $appointment)) {
                return $request;
            }

            return $response->setStatusCode(403)->setJSON([
                "message" => "Los pacientes solo pueden cancelar sus turnos confirmados"
            ]);
        }

        // MÉDICO
        if ($roleId === Role::MEDICAL) {

            // El médico solo puede modificar sus propios turnos
            if (!$permissionsService->canEditAppointment($roleId, $userId, $appointment)) {
                return $response->setStatusCode(403)->setJSON([
                    "message" => "No podés modificar turnos de otro médico"
                ]);
            }

            // Médico no puede eliminar
            if ($method === 'delete') {
                return $response->setStatusCode(403)->setJSON([
                    "message" => "Los médicos no pueden eliminar turnos. Solo pueden cambiar el estado."
                ]);
            }

            return $request;
        }

        // Cualquier otro rol no autorizado
        return $response->setStatusCode(403)->setJSON([
            "message" => "No tenés permiso para modificar este turno"
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}