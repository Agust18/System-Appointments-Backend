<?php
namespace App\Converter\MedicalService\Projection;

use App\Dto\Response\MedicalService\PublicMedicalServiceResponse;
use App\Entity\MedicalService\Projection\MedicalServiceProjection;

final class MedicalServiceProjectionToPublicMedicalServiceResponseConverter
{
    public function convert(MedicalServiceProjection $projection): PublicMedicalServiceResponse
    {
        return new PublicMedicalServiceResponse(
            $projection->getServiceId(),
            $projection->getName(),
            $projection->getDescription(),
            $projection->getEffectiveDurationMinutes(),
            $projection->getEffectivePrice()
        );
    }
}
