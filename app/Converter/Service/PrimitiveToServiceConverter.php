<?php
namespace App\Converter\Service;

use App\Entity\Service\Service;
use DateTime;

final class PrimitiveToServiceConverter
{
    public function convert(object $primitive): Service
    {
        return new Service(
            $primitive->id ? (int)$primitive->id : null,
            $primitive->name,
            $primitive->description ?? null,
            (int)$primitive->duration_minutes,
            isset($primitive->buffer_minutes) ? (int)$primitive->buffer_minutes : 0,
            (float)$primitive->price,
            (bool)$primitive->is_active,
            $primitive->created_at ? new DateTime($primitive->created_at) : null,
            $primitive->updated_at ? new DateTime($primitive->updated_at) : null
        );
    }
}
