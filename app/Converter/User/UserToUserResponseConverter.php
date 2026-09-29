<?php

namespace App\Converter\User;

use App\Dto\Response\User\UserResponse;
use App\Entity\User\User;

final class UserToUserResponseConverter
{

    public function convert(User $user): UserResponse
    {
        return new UserResponse(
        $user->getId(),
        $user->getFirstName(),
        $user->getLastName(),
        $user->getFullName(),
        $user->getEmail(),
        $user->getDni(),
        $user->getIsActive(),
        $user->getRoleId(),
        $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        $user->getUpdatedAt()?->format('Y-m-d H:i:s')
);
    }
}