<?php
namespace App\Converter\Patient;

use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;

final class PatientToPatientResponseConverter
{
    public function convert(Patient $patient): PatientResponse
    {
        return new PatientResponse(
            $patient->getId(),
            $patient->getUserId(),
            $patient->getBirthDate()?->format('Y-m-d'),
            $patient->getCreatedAt()?->format('Y-m-d H:i:s'),
            $patient->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}