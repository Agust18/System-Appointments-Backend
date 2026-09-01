<?php
namespace App\Services\User\Validations;

use App\Dto\Request\User\UserUpdaterRequest;
use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Services\Role\RoleFinderService;

final class UserUpdateValidatorService
{
    private UserModel $userModel;
    private RoleFinderService $roleFinderService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleFinderService = new RoleFinderService();
    }

    public function validate(User $user, UserUpdaterRequest $request): void
    {
        // Validar email único 
        if ($request->getEmail() && $request->getEmail() !== $user->getEmail()) {
            $existingEmail = $this->userModel->findByEmail($request->getEmail());
            if ($existingEmail && $existingEmail->getId() !== $user->getId()) {
                throw new \Exception('El email ya está registrado por otro usuario');
            }
        }

        //  Validar DNI único 
        if ($request->getDni() && $request->getDni() !== $user->getDni()) {
            $existingDni = $this->userModel->findByDni($request->getDni());
            if ($existingDni && $existingDni->getId() !== $user->getId()) {
                throw new \Exception('El DNI ya está registrado por otro usuario');
            }
        }

        // Validar que el rol exista 
        if ($request->getRoleId() && $request->getRoleId() !== $user->getRoleId()) {
            $role = $this->roleFinderService->find($request->getRoleId());
            if (!$role) {
                throw new \Exception('El rol seleccionado no existe');
            }
        }

        //  Validar contraseña 
        if ($request->getPassword() && strlen($request->getPassword()) < 6) {
            throw new \Exception('La contraseña debe tener al menos 6 caracteres');
        }
    }
}