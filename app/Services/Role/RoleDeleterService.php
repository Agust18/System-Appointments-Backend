<?php
namespace App\Services\Role;

use App\Exception\Role\RoleInUsedException;
use App\Models\Role\RoleModel;
use App\Models\User\UserModel;

final class RoleDeleterService
{
    private RoleModel $roleModel;
    private RoleFinderService $roleFinderService;
      private UserModel $userModel;


    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->roleFinderService = new RoleFinderService();
         $this->userModel = new UserModel();
    }

    public function delete(int $id): void
    {
       
        $this->roleFinderService->find($id);

        $user = $this->userModel->countByRoleId($id);
        
       if ($user>0){
            throw new RoleInUsedException($id, $user);
       }
        
        $this->roleModel->delete($id);
    }
}