<?php
namespace App\Converter\Medical\Projection;

use App\Dto\Response\Medical\Projection\MedicalProjectionResponse;
use App\Entity\Medical\Projection\MedicalProjection;

final class MedicalProjectionToMedicalProjectionResponseConverter
{
    public function convert(MedicalProjection $medical): MedicalProjectionResponse
    {
        return new MedicalProjectionResponse(
            $medical->getId(),
            $medical->getUserId(),
            $medical->getSpecialtyId(),
            $medical->getMedicoNombre(),
            $medical->getEmail(),
            $medical->getPhone(),
            $medical->getEspecialidadNombre(),
            $medical->getTuition(),
            $medical->isActive(),
            $medical->getCreatedAt()->format('Y-m-d H:i:s'),
            $medical->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}