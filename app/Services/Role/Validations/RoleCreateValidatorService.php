<?php
namespace App\Services\Role\Validations;

use App\Dto\Request\Role\RoleRequest;
use App\Models\Role\RoleModel;
use App\Exception\Role\RoleAlreadyExistsException;

final class RoleCreateValidatorService
{
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();

    }

    public function validate(RoleRequest $request): void
    {
        $existing = $this->roleModel->findByName($request->getName());
        if ($existing) {
            throw new RoleAlreadyExistsException($request->getName());
        }

    }
}