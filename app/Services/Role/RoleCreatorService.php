<?php
namespace App\Services\Role;

use App\Converter\Role\RoleToRoleResponseConverter;
use App\Dto\Request\Role\RoleRequest;
use App\Dto\Response\Role\RoleResponse;
use App\Entity\Role\Role;
use App\Models\Role\RoleModel;
use App\Services\Role\Validations\RoleCreateValidatorService;


final class RoleCreatorService
{
    private RoleModel $roleModel;
    private RoleToRoleResponseConverter $converter;
    private RoleCreateValidatorService $validator;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->converter = new RoleToRoleResponseConverter();
        $this->validator = new RoleCreateValidatorService();
    }

    public function create(RoleRequest $request): RoleResponse
    {
        $this->validator->validate($request);
        
        $role = Role::fromRequest($request);
        
        $newRole = $this->roleModel->insert($role);
        
        return $this->converter->convert($newRole);
    }
}