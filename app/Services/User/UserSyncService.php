<?php
namespace App\Services\User;

use App\Dto\Request\User\UserUpdaterRequest;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;
use App\Constants\Role;

final class UserSyncService
{
    private MedicalModel $medicalModel;
   
    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
      
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
        if ($oldRoleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            if ($medical) {
                $medical->modifyIsActive($isActive);
                $this->medicalModel->update($medical);
            }
        }
         
    }

    public function syncRoleChange(int $userId, int $oldRoleId, int $newRoleId): void
    {
        if ($newRoleId === Role::MEDICAL && $oldRoleId !== Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            
            if ($medical) {
                $medical->modifyIsActive(true);
                $this->medicalModel->update($medical);
            }
            
        }
    }

    
}
