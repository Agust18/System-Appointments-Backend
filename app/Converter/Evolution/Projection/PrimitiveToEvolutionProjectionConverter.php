<?php
namespace App\Converter\Evolution\Projection;

use App\Converter\Evolution\PrimitiveToEvolutionConverter;
use App\Entity\Evolution\Projection\EvolutionProjection;

final class PrimitiveToEvolutionProjectionConverter
{
    private PrimitiveToEvolutionConverter $evolutionConverter;

    public function __construct()
    {
        $this->evolutionConverter = new PrimitiveToEvolutionConverter();
    }

    public function convert(object $primitive): EvolutionProjection
    {
        return new EvolutionProjection(
            $this->evolutionConverter->convert($primitive),
            (string) $primitive->medical_name,
            (string) $primitive->appointment_fecha,
            substr((string) $primitive->appointment_hora, 0, 5),
            (string) $primitive->service_name
        );
    }
}
