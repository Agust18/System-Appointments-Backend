<?php

namespace App\Filters;

use App\Constants\Role;
use App\Filters\Support\ResolvesEntityByRouteSegment;
use App\Models\Medical\MedicalModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MedicalViewPermission implements FilterInterface
{
    use ResolvesEntityByRouteSegment;

    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');
        $response = \Config\Services::response();
        $medicalModel = new MedicalModel();

        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return $request;
        }

        [$medical, $byUserId] = $this->resolveEntityByRouteSegment(
            $request,
            fn (int $targetUserId) => $medicalModel->findByUserId($targetUserId),
            fn (int $medicalId) => $medicalModel->find($medicalId)
        );

        if (!$medical) {
            return $response->setStatusCode(404)->setJSON([
                'message' => 'Médico no encontrado'
            ]);
        }

        // /medicals/user/{userId} es solo para que el propio médico consulte su perfil.
        if ($roleId === Role::PATIENT && !$byUserId) {
            return $request;
        }

        if ($roleId === Role::MEDICAL && $medical->getUserId() === $userId) {
            return $request;
        }

        return $response->setStatusCode(403)->setJSON([
            'message' => $byUserId
                ? 'No tenés permiso para consultar este médico por usuario'
                : 'No tenés permiso para consultar este médico'
        ]);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
    }
}
