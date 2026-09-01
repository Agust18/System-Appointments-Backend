<?php
namespace App\Converter\User;
use App\Entity\User\User;
use DateTime;

final class PrimitiveToUserConverte
{
    public function convert(object $primitive): User
    {
        return new User(
            $primitive->id,
            $primitive->username,
            $primitive->email,
            $primitive->password,
            $primitive->dni,
            $primitive->is_active,
            $primitive->token,
            new DateTime($primitive->token_expiration_date),
            $primitive->role_id,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)


        );

    }
}


