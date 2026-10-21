<?php
namespace App\Converter\Evolution;

use App\Dto\Response\Evolution\EvolutionResponse;
use App\Entity\Evolution\Evolution;

final class EvolutionToEvolutionResponseConverter
{
    public function convert(Evolution $evolution): EvolutionResponse
    {
        return new EvolutionResponse(
            $evolution->getId(),
            $evolution->getPatientId(),
            $evolution->getMedicalId(),
            $evolution->getAppointmentId(),
            $evolution->getDiagnosis(),
            $evolution->getObservations(),
            $evolution->getTreatment(),
            $evolution->getIndications(),
            $evolution->getCorrectsId(),
            $evolution->isSigned() ? 'signed' : 'draft',
            $evolution->getSignedAt()?->format('Y-m-d H:i:s'),
            $evolution->getCreatedAt()?->format('Y-m-d H:i:s'),
            $evolution->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
