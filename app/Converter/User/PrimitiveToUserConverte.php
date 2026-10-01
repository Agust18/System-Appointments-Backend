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
            $primitive->first_name,
            $primitive->last_name,
            $primitive->email,
            $primitive->phone ?? null,
            $primitive->password,
            $primitive->dni,
            (bool) $primitive->is_active,
            (bool) $primitive->is_verified,
            $primitive->token,
            $primitive->token_expiration_date ? new DateTime($primitive->token_expiration_date) : null,
            $primitive->role_id,
            $primitive->created_at ? new DateTime($primitive->created_at) : null,
            $primitive->updated_at ? new DateTime($primitive->updated_at) : null,


        );

    }
}


