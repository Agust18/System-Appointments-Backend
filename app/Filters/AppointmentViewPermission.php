<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Services\Permissions\PermissionsService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AppointmentViewPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $response = \Config\Services::response();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        $appointmentId = (int) $request->getUri()->getSegment(2);
        $appointment = (new AppointmentModel())->find($appointmentId);

        if (!$appointment) {
            return $response->setStatusCode(404)->setJSON([
                'message' => 'Turno no encontrado'
            ]);
        }

        if ((new PermissionsService())->canViewAppointment($roleId, $userId, $appointment)) {
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            'message' => 'No tenés permiso para consultar este turno'
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}
