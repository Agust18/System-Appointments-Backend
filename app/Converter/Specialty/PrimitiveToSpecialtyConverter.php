<?php
namespace App\Converter\Specialty;

use App\Entity\Specialty\Specialty;
use DateTime;

final class PrimitiveToSpecialtyConverter
{
    public function convert(object $primitive): Specialty
    {
        return new Specialty(
            $primitive->id,
            $primitive->name,
            $primitive->description ?? null,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
            