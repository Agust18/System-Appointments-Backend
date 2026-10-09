<?php

namespace App\Services\User;

use App\Exception\User\UserNotFoundException;
use App\Models\User\UserModel;
use App\Services\User\Validations\UserDeleterValidatorService;
final class UserDeleterService
{

    private UserModel $userModel;
    private UserFinderService $userFinderService;
    private UserDeleterValidatorService $validator;


    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userFinderService = new UserFinderService();
        $this->validator        = new UserDeleterValidatorService();
    }

    public function delete(int $id): void
    {
        $user = $this->userFinderService->find($id);
        

        $this->validator->validate($id,$user);


        $this->userModel->delete($id);
    }
}