<?php

namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\User\UserRequest;
use App\Dto\Response\User\UserResponse;
use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Services\User\Validations\UserCreateValidatorService;

final class UserCreatorService
{
    private UserModel $userModel;
    private UserToUserResponseConverter $converter;
    private UserCreateValidatorService $validator;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
        $this->validator = new UserCreateValidatorService();
    }

    public function create(UserRequest $request, bool $isVerified = false): UserResponse
    {
        $this->validator->validate($request);

        $user = User::convertFromRequest($request);

        if ($isVerified) {
            $user->markAsVerified();
        }


        $newUser = $this->userModel->insert($user);

        $userResponse = $this->converter->convert($newUser);

        return $userResponse;
    }
}