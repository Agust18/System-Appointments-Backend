<?php
namespace App\Services\User;

use App\Dto\Request\User\UserUpdaterRequest;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;

final class UserSyncService
{
    private MedicalModel $medicalModel;
    private PatientModel $patientModel;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->patientModel = new PatientModel();
    }

    public function syncAll(int $userId, bool $oldIsActive, int $oldRoleId, UserUpdaterRequest $request): void
    {
        $newIsActive = $request->getIsActive();
        $newRoleId = $request->getRoleId() ?? $oldRoleId;

        if ($newIsActive !== null && $newIsActive !== $oldIsActive) {
            $this->syncActiveStatus($userId, $newIsActive, $oldRoleId);
        }

        if ($newRoleId !== $oldRoleId) {
            $this->syncRoleChange($userId, $oldRoleId, $newRoleId);
        }
    }

    public function syncActiveStatus(int $userId, bool $isActive, int $oldRoleId): void
    {
        if ($oldRoleId === 2) {
            $medical = $this->medicalModel->findByUserId($userId);
            if ($medical) {
                $medical->modifyIsActive($isActive);
                $this->medicalModel->update($medical);
            }
        }
    }

    public function syncRoleChange(int $userId, int $oldRoleId, int $newRoleId): void
    {
        if ($newRoleId === 2 && $oldRoleId !== 2) {
            $medical = $this->medicalModel->findByUserId($userId);
            
            if ($medical) {
                // ✅ Ya existe → reactivar
                $medical->modifyIsActive(true);
                $this->medicalModel->update($medical);
            }
            // } else {
            //     // ✅ No existe → crear nuevo médico
            //     $medical = new Medical(
            //         null,      // id
            //         $userId,   // user_id
            //         null,      // tuition (pendiente)
            //         null,      // specialtyId (pendiente)
            //         null,      // phone
            //         true,      // is_active
            //         null,      // created_at
            //         null       // updated_at
            //     );
            //     $this->medicalModel->insert($medical);
            // }
        }
    }
}
