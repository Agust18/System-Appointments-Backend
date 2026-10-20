<?php

namespace App\Services\Auth;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\Auth\UserLoginRequest;
use App\Dto\Response\User\UserLoginResponse;
use App\Exception\Auth\NotVerifiedException;
use App\Exception\Auth\WrongPasswordException;
use App\Models\User\UserModel;
use App\Services\User\UserFinderByEmailService;
use Exception;

final class UserLoginService
{

    private UserModel $userModel;
    private UserFinderByEmailService $userFinderByEmailService;
    private UserToUserResponseConverter $converter;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userFinderByEmailService = new UserFinderByEmailService();
        $this->converter = new UserToUserResponseConverter();
    }

    public function login(UserLoginRequest $request): UserLoginResponse
    {
        $user = $this->userFinderByEmailService->find($request->getEmail());
       
        if (!$user->getIsActive()) {
            throw new Exception("usuario no está activo, comunicar con el administrador.",409);
        }

        if (!$user->verifyPassword($request->getPassword())) {
            throw new WrongPasswordException();
        }

        if (!$user->getIsVerified()) {
            throw new NotVerifiedException();
        }

        $user->generateToken();

        $this->userModel->update($user);

        return new UserLoginResponse(
            $user->getToken(),
            $this->converter->convert($user)
        );

    }
}
