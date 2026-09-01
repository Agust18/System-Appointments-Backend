<?php 

namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Exception\User\UserNotFoundByEmailException;

final class UserFinderByEmailService {

    private UserModel $userModel;
    private UserToUserResponseConverter $converter;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
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