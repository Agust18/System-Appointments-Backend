<?php
namespace App\Converter\Medical;

use App\Entity\Medical\Medical;
use DateTime;

final class PrimitiveToMedicalConverter
{
    public function convert(object $primitive): Medical
    {
        return new Medical(
            $primitive->id,
            $primitive->user_id,
            $primitive->tuition,
            $primitive->specialty_id,
            $primitive->phone ?? null,
            $primitive->is_active ?? true,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}