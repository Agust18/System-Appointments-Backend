<?php

namespace App\Converter\Medical\Projection;

use App\Dto\Response\Medical\MedicalAvailableResponse;
use App\Entity\Medical\Projection\MedicalProjection;

final class MedicalProjectionToMedicalAvailableResponseConverter
{
    public function convert(MedicalProjection $medical): MedicalAvailableResponse
    {
        return new MedicalAvailableResponse(
            $medical->getId(),
            $medical->getMedicoNombre(),
            $medical->getEspecialidadNombre(),
            $medical->isActive()
        );
    }
}
