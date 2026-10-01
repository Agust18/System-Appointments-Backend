<?php

namespace App\Converter\Patient\Projection;

use App\Dto\Response\Patient\Projection\PatientProjectionResponse;
use App\Entity\Patient\Projection\PatientProjection;

final class PatientProjectionToPatientProjectionResponseConverter
{
    public function convert(PatientProjection $patient): PatientProjectionResponse
    {
        return new PatientProjectionResponse(
            $patient->getId(),
            $patient->getUserId(),
            $patient->getPacienteNombre(),
            $patient->getDni(),
            $patient->getEmail(),
            $patient->getPhone(),
            $patient->getBirthDate()?->format('Y-m-d'),
            $patient->isActive(),
            $patient->getCreatedAt()?->format('Y-m-d H:i:s'),
            $patient->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
