<?php
namespace App\Filters;

use App\Constants\Role;
use App\Models\Appointment\AppointmentModel;
use App\Models\Patient\PatientModel;
use App\Models\Medical\MedicalModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AppointmentOwnerOnly implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $response = \Config\Services::response();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method = strtolower($method);

       

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        $appointmentId = (int) $request->getUri()->getSegment(2);

        $appointmentModel = new AppointmentModel();
        $appointment = $appointmentModel->find($appointmentId);

        if (!$appointment) {
            return $response->setStatusCode(404)->setJSON([
                "message" => "Turno no encontrado"
            ]);
        }


        if ($roleId === Role::PATIENT) {
            return $response->setStatusCode(403)->setJSON([
                "message" => "Los pacientes no pueden modificar ni eliminar turnos"
            ]);
        }

        if ($roleId === Role::MEDICAL) {
            $medicalModel = new MedicalModel();
            $medical = $medicalModel->findByUserId($userId);

            if (!$medical || $appointment->getMedicalId() !== $medical->getId()) {
                return $response->setStatusCode(403)->setJSON([
                    "message" => "No podés modificar turnos de otro médico"
                ]);
            }


            if ($method === 'delete') {
                return $response->setStatusCode(403)->setJSON([
                    "message" => "Los médicos no pueden eliminar turnos. Solo pueden cambiar el estado."
                ]);
            }


            return $request;
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}