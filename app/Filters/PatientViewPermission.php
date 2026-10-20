<?php

namespace App\Filters;

use App\Constants\Role;
use App\Filters\Support\ResolvesEntityByRouteSegment;
use App\Models\Patient\PatientModel;
use App\Services\Permissions\PermissionsService;
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
        $patientModel = new PatientModel();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        $patient = $patientModel->find((int) ($request->getUri()->getSegments()[1] ?? 0));

        if (!$patient) {
            return $response->setStatusCode(404)->setJSON([
                'message' => 'Paciente no encontrado'
            ]);
        }

        if ((new PermissionsService())->canViewPatient($roleId, $userId, $patient->getId())) {
            return $request;
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
