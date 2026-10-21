<?php
namespace App\Converter\Evolution\Projection;

use App\Entity\Evolution\Projection\EvolutionAccessLogProjection;
use DateTime;

final class PrimitiveToEvolutionAccessLogProjectionConverter
{
    public function convert(object $primitive): EvolutionAccessLogProjection
    {
        return new EvolutionAccessLogProjection(
            (int) $primitive->id,
            (int) $primitive->user_id,
            (string) $primitive->user_name,
            (int) $primitive->patient_id,
            (string) $primitive->patient_name,
            $primitive->evolution_id !== null ? (int) $primitive->evolution_id : null,
            (string) $primitive->action,
            new DateTime($primitive->created_at)
        );
    }
}
