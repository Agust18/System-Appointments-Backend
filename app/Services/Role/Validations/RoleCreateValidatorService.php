<?php
namespace App\Services\Role\Validations;

use App\Dto\Request\Role\RoleRequest;
use App\Models\Role\RoleModel;
use App\Exception\Role\RoleAlreadyExistsException;
use App\Exception\Role\RoleNameRequiredException;
use App\Exception\Role\RoleNameTooLongException;
use App\Exception\Role\RoleNameTooShortException;

final class RoleCreateValidatorService
{
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();

    }

    public function validate(RoleRequest $request): void
    {

        if (empty($request->getName())) {
            throw new RoleNameRequiredException();
        }

        if (strlen($request->getName()) < 3) {
            throw new RoleNameTooShortException();
        }

        if (strlen($request->getName()) > 20) {
           throw new RoleNameTooLongException();
        }

        $existing = $this->roleModel->findByName($request->getName());
        if ($existing) {
            throw new RoleAlreadyExistsException($request->getName());
        }

    }
}