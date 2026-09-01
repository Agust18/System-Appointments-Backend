<?php
namespace App\Services\Role;

use App\Converter\Role\RoleToRoleResponseConverter;
use App\Dto\Response\Role\RoleResponse;
use App\Entity\Role\Role;
use App\Exception\Role\RoleNotFoundException;
use App\Models\Role\RoleModel;

final class RoleFinderService
{
    private RoleModel $roleModel;
    private RoleToRoleResponseConverter $converter;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->converter = new RoleToRoleResponseConverter();
    }

    public function find(int $id): Role
    {
        $role = $this->roleModel->find($id);
        
        if (!$role) {
            throw new RoleNotFoundException($id);
        }
        
        return $role;
    }

    public function findResponse(int $id): RoleResponse
    {
        $role = $this->find($id);
        return $this->converter->convert($role);
    }

    public function findByName(string $name): ?Role
    {
        return $this->roleModel->findByName($name);
    }
}