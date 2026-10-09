<?php
namespace App\Services\Role;

use App\Converter\Role\RoleToRoleResponseConverter;
use App\Dto\Request\Role\RoleUpdateRequest;
use App\Dto\Response\Role\RoleResponse;
use App\Models\Role\RoleModel;
use App\Exception\Role\RoleAlreadyExistsException;

final class RoleUpdaterService
{
    private RoleModel $roleModel;
    private RoleToRoleResponseConverter $converter;
    private RoleFinderService $roleFinderService;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->converter = new RoleToRoleResponseConverter();
        $this->roleFinderService = new RoleFinderService();
    }

    public function update(RoleUpdateRequest $request, int $id): RoleResponse
    {
        $role = $this->roleFinderService->find($id);
        
        if ($request->getName() !== null && $request->getName() !== $role->getName()) {
            $existing = $this->roleModel->findByName($request->getName());
            if ($existing) {
                throw new RoleAlreadyExistsException($request->getName());
            }
            $role->setName($request->getName());
        }
        
        $updatedRole = $this->roleModel->update($role);
        
        return $this->converter->convert($updatedRole);
    }
}