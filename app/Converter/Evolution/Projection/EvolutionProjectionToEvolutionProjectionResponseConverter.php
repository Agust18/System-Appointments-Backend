<?php
namespace App\Converter\Evolution\Projection;

use App\Dto\Response\Evolution\Projection\EvolutionProjectionResponse;
use App\Entity\Evolution\Projection\EvolutionProjection;

final class EvolutionProjectionToEvolutionProjectionResponseConverter
{
    public function convert(EvolutionProjection $projection): EvolutionProjectionResponse
    {
        $evolution = $projection->getEvolution();

        return new EvolutionProjectionResponse(
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
            $evolution->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $projection->getMedicalName(),
            $projection->getAppointmentDate(),
            $projection->getAppointmentTime(),
            $projection->getServiceName()
        );
    }
}
