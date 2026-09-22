<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;
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

        // El paciente solo puede cancelar su propio turno pendiente.
        if ($roleId === Role::PATIENT) {
            if ($method === 'put') {
                $parameters = json_decode($request->getBody(), true) ?? [];
                $patient = (new PatientModel())->findByUserId($userId);

                if (
                    $patient &&
                    $appointment->getPatientId() === $patient->getId() &&
                    $appointment->getStatus() === 'pending' &&
                    ($parameters['status'] ?? null) === 'cancelled'
                ) {
                    return $request;
                }
            }

            return $response->setStatusCode(403)->setJSON([
                "message" => "Los pacientes solo pueden cancelar sus turnos pendientes"
            ]);
        }

        // MÉDICO
        if ($roleId === Role::MEDICAL) {

            $medicalModel = new MedicalModel();
            $medical = $medicalModel->findByUserId($userId);

            // El médico solo puede modificar sus propios turnos
            if (
                !$medical ||
                $appointment->getMedicalId() !== $medical->getId()
            ) {
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