<?php

namespace App\Filters;

use App\Constants\Role;
use App\Models\Medical\MedicalModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MedicalViewPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $response = \Config\Services::response();
        $medicalModel = new MedicalModel();
        $segments = $request->getUri()->getSegments();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        if (($segments[1] ?? null) === 'user') {
            $targetUserId = (int) ($segments[2] ?? 0);
            $medical = $medicalModel->findByUserId($targetUserId);

            if (!$medical) {
                return $response->setStatusCode(404)->setJSON([
                    'message' => 'Médico no encontrado'
                ]);
            }

            if ($roleId === Role::MEDICAL && $targetUserId === $userId) {
                return $request;
            }

            return $response->setStatusCode(403)->setJSON([
                'message' => 'No tenés permiso para consultar este médico por usuario'
            ]);
        }

        $medicalId = (int) ($segments[1] ?? 0);
        $medical = $medicalModel->find($medicalId);

        if (!$medical) {
            return $response->setStatusCode(404)->setJSON([
                'message' => 'Médico no encontrado'
            ]);
        }

        if ($roleId === Role::PATIENT) {
            return $request;
        }

        if ($roleId === Role::MEDICAL && $medical->getUserId() === $userId) {
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            'message' => 'No tenés permiso para consultar este médico'
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}
