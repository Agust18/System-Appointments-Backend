<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Models\Patient\PatientModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PatientViewPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $response = \Config\Services::response();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        $patientModel = new PatientModel();
        $segments = $request->getUri()->getSegments();
        $patient = null;

        if (($segments[1] ?? null) === 'user') {
            $targetUserId = (int) ($segments[2] ?? 0);
            $patient = $patientModel->findByUserId($targetUserId);
        } else {
            $patientId = (int) ($segments[1] ?? 0);
            $patient = $patientModel->find($patientId);
        }

        if (!$patient) {
            return $response->setStatusCode(404)->setJSON([
                'message' => 'Paciente no encontrado'
            ]);
        }

        if ($roleId === Role::PATIENT && $patient->getUserId() === $userId) {
            return $request;
        }

        if ($roleId === Role::MEDICAL) {
            $appointments = (new AppointmentModel())->findByMedicalUserId($userId);

            foreach ($appointments as $appointment) {
                if ($appointment->getPatientId() === $patient->getId()) {
                    return $request;
                }
            }
        }

        return $response->setStatusCode(403)->setJSON([
            'message' => 'No tenés permiso para consultar este paciente'
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}
