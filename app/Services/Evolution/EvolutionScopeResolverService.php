<?php
namespace App\Services\Evolution;

use App\Constants\Role;
use App\Exception\Evolution\EvolutionAccessDeniedException;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Medical\MedicalModel;
use App\Services\Permissions\PermissionsService;

/**
 * Qué evoluciones puede leer quien llama: el ADMIN todas las del Tenant (null = sin filtro de médico),
 * el MEDICAL solo las suyas (su medicalId). Cualquier otro rol no entra.
 */
final class EvolutionScopeResolverService
{
    private PermissionsService $permissionsService;
    private MedicalModel $medicalModel;

    public function __construct()
    {
        $this->permissionsService = new PermissionsService();
        $this->medicalModel = new MedicalModel();
    }

    public function medicalIdFor(int $roleId, int $userId): ?int
    {
        if (!$this->permissionsService->canViewEvolutions($roleId)) {
            throw new EvolutionAccessDeniedException();
        }

        if ($roleId !== Role::MEDICAL) {
            return null;
        }

        $medical = $this->medicalModel->findByUserId($userId);
        if (!$medical) {
            throw new MedicalNotFoundExceptionByUserException($userId);
        }

        return $medical->getId();
    }
}
