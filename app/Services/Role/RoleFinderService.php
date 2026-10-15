<?php
namespace App\Services\Role;

use App\Entity\Role\Role;
use App\Exception\Role\RoleNotFoundException;
use App\Models\Role\RoleModel;

final class RoleFinderService
{
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    public function find(int $id): Role
    {
        $role = $this->roleModel->find($id);

        if (!$role) {
            throw new RoleNotFoundException($id);
        }

        return $role;
    }
}
