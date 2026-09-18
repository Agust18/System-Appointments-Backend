<?php 

namespace App\Services\User;


use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Exception\User\UserNotFoundByEmailException;

final class UserFinderByEmailService {

    private UserModel $userModel;
 

    public function __construct() {
        $this->userModel = new UserModel();
       
    }

    public function find(string $email): User 
    {
        $user = $this->userModel->findByEmail($email);

        if (empty($user)) {
            throw new UserNotFoundByEmailException($email);
        }

        return $user;
    }
}