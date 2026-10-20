<?php
namespace App\Services\User\Validations;

use App\Constants\Role;
use App\Dto\Request\User\UserUpdaterRequest;
use App\Entity\User\User;
use App\Exception\Role\RoleNotFoundException;
use App\Exception\User\DniAlreadyRegisteredException;
use App\Exception\User\EmailAlreadyRegisteredException;
use App\Exception\Validation\ValidationException;
use App\Models\User\UserModel;

final class UserUpdateValidatorService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function validate(User $user, UserUpdaterRequest $request): void
    {
        // Validar email único
        if ($request->getEmail() && $request->getEmail() !== $user->getEmail()) {
            $existingEmail = $this->userModel->findByEmail($request->getEmail());
            if ($existingEmail && $existingEmail->getId() !== $user->getId()) {
                throw new EmailAlreadyRegisteredException();
            }
        }

        //  Validar DNI único
        if ($request->getDni() && $request->getDni() !== $user->getDni()) {
            $existingDni = $this->userModel->findByDni($request->getDni());
            if ($existingDni && $existingDni->getId() !== $user->getId()) {
                throw new DniAlreadyRegisteredException();
            }
        }

        // Validar que el rol exista
        if ($request->getRoleId() && $request->getRoleId() !== $user->getRoleId() && !Role::isValid($request->getRoleId())) {
            throw new RoleNotFoundException($request->getRoleId());
        }

        // El médico tiene su propia fila (medicals): cambiar el rol por acá dejaría un usuario
        // sin perfil o un perfil huérfano.
        $newRoleId = $request->getRoleId();
        if ($newRoleId !== null && $newRoleId !== $user->getRoleId()) {
            if ($newRoleId === Role::MEDICAL || $user->getRoleId() === Role::MEDICAL) {
                throw new ValidationException([
                    'roleId' => 'El rol de médico no se puede asignar ni quitar desde usuarios: usá médicos.',
                ]);
            }
        }
    }
}