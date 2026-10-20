<?php

namespace App\Services\User\Validations;


use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Exception\Role\RoleNotFoundException;
use App\Exception\User\DniAlreadyRegisteredException;
use App\Exception\User\EmailAlreadyRegisteredException;
use App\Models\User\UserModel;

final class UserCreateValidatorService
{

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function validate(UserRequest $request): void
    {

        $existeUser = $request->getEmail() !== null
            ? $this->userModel->findByEmail($request->getEmail())
            : null;
        if ($existeUser) {
            throw new EmailAlreadyRegisteredException();
        }

        $existeDni = $this->userModel->findByDni($request->getDni());
        if ($existeDni) {
            throw new DniAlreadyRegisteredException();
        }


        if ($request->getRoleId() && !Role::isValid($request->getRoleId())) {
            throw new RoleNotFoundException($request->getRoleId());
        }
    }
}
