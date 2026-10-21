<?php
namespace App\Converter\Evolution;

use App\Entity\Evolution\Evolution;
use DateTime;

final class PrimitiveToEvolutionConverter
{
    public function convert(object $primitive): Evolution
    {
        return new Evolution(
            (int) $primitive->id,
            (int) $primitive->patient_id,
            (int) $primitive->medical_id,
            (int) $primitive->appointment_id,
            $primitive->diagnosis,
            $primitive->observations,
            $primitive->treatment,
            $primitive->indications,
            $primitive->corrects_id !== null ? (int) $primitive->corrects_id : null,
            $primitive->signed_at !== null ? new DateTime($primitive->signed_at) : null,
            new DateTime($primitive->created_at),
            new DateTime($primitive->updated_at)
        );
    }
}
