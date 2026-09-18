<?php

namespace App\Converter\UserToken;

use App\Entity\UserToken\UserToken;
use DateTime;

final class PrimitiveToUserTokenConverter
{
    public function convert(object $primitive): UserToken
    {
        return new UserToken(
            $primitive->id,
            $primitive->user_id,
            $primitive->token,
            $primitive->type,
            new DateTime($primitive->expires_at),
            $primitive->used_at ? new DateTime($primitive->used_at) : null,
            new DateTime($primitive->created_at),
        );
    }
}
