<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;
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

        if ($roleId === Role::PATIENT) {
            $patient = (new PatientModel())->findByUserId($userId);

            if ($patient && $appointment->getPatientId() === $patient->getId()) {
                return $request;
            }
        }

        if ($roleId === Role::MEDICAL) {
            $medical = (new MedicalModel())->findByUserId($userId);

            if ($medical && $appointment->getMedicalId() === $medical->getId()) {
                return $request;
            }
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
