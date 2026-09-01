<?php

namespace App\Services\User;


use App\Converter\User\UserToUserResponseConverter;
use App\Entity\User\User;
use App\exception\User\UserNotFoundException;
use App\Models\User\UserModel;
use App\Dto\Response\User\UserResponse;

final class UserFinderService
{
    private UserModel $userModel;
    private UserToUserResponseConverter $converter;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
    }

    public function find(int $id): User
    {
        $user = $this->userModel->find($id);
        if (empty($user)) {
            throw new UserNotFoundException($id);
        }
        return $user;
    }

    public function findResponse(int $id): UserResponse
    {
        $user = $this->find($id);
        $response = $this->converter->convert($user);
        return $response;
    }
}
