<?php
namespace App\Converter\Evolution\Projection;

use App\Dto\Response\Evolution\Projection\EvolutionAccessLogProjectionResponse;
use App\Entity\Evolution\Projection\EvolutionAccessLogProjection;

final class EvolutionAccessLogProjectionToEvolutionAccessLogProjectionResponseConverter
{
    public function convert(EvolutionAccessLogProjection $log): EvolutionAccessLogProjectionResponse
    {
        return new EvolutionAccessLogProjectionResponse(
            $log->getId(),
            $log->getUserId(),
            $log->getUserName(),
            $log->getPatientId(),
            $log->getPatientName(),
            $log->getEvolutionId(),
            $log->getAction(),
            $log->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }
}
