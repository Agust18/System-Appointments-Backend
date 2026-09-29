<?php

namespace App\Services\User\Validations;


use App\Dto\Request\User\UserRequest;
use App\Exception\Role\RoleNotFoundException;
use App\Exception\User\DniAlreadyRegisteredException;
use App\Exception\User\EmailAlreadyRegisteredException;
use App\Exception\User\UsernameAlreadyRegisteredException;
use App\Models\User\UserModel;
use App\Services\Role\RoleFinderService;

final class UserCreateValidatorService
{

    private UserModel $userModel;
    private RoleFinderService $roleFinderService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleFinderService = new RoleFinderService();
    }

    public function validate(UserRequest $request): void
    {
        $existeUsername = $this->userModel->findByUsername($request->getUsername());
        if ($existeUsername) {
            throw new UsernameAlreadyRegisteredException();
        }

        $existeUser = $this->userModel->findByEmail($request->getEmail());
        if ($existeUser) {
            throw new EmailAlreadyRegisteredException();
        }

        $existeDni = $this->userModel->findByDni($request->getDni());
        if ($existeDni) {
            throw new DniAlreadyRegisteredException();
        }
       

        if ($request->getRoleId()) {
            $role = $this->roleFinderService->find($request->getRoleId());
            if (!$role) {
                throw new RoleNotFoundException($request->getRoleId());
            }
        }
    }
}
