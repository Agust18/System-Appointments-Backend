<?php

namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\User\UserLoginRequest;
use App\Dto\Response\User\UserLoginResponse;
use App\exception\User\UserNotFoundException;
use App\Exception\User\UserNotVerifiedException;
use App\Exception\User\UserWrongPasswordException;
use App\Models\User\UserModel;
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
            throw new UserWrongPasswordException();
        }

        if (!$user->getIsVerified()) {
            throw new UserNotVerifiedException();
        }

        $user->generateToken();

        $this->userModel->update($user);

        return new UserLoginResponse(
            $user->getToken(),
            $this->converter->convert($user)
        );

        // return new UserLoginResponse($user->getToken());
    }
}
