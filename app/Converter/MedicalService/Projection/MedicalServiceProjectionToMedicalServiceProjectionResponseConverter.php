<?php
namespace App\Converter\MedicalService\Projection;

use App\Dto\Response\MedicalService\Projection\MedicalServiceProjectionResponse;
use App\Entity\MedicalService\Projection\MedicalServiceProjection;

final class MedicalServiceProjectionToMedicalServiceProjectionResponseConverter
{
    public function convert(MedicalServiceProjection $projection): MedicalServiceProjectionResponse
    {
        return new MedicalServiceProjectionResponse(
            $projection->getMedicalServiceId(),
            $projection->getMedicalId(),
            $projection->getServiceId(),
            $projection->getName(),
            $projection->getDescription(),
            $projection->getBaseDurationMinutes(),
            $projection->getBufferMinutes(),
            $projection->getBasePrice(),
            $projection->getSlotStepMinutes(),
            $projection->getCustomPrice(),
            $projection->getCustomDurationMinutes(),
            $projection->getEffectiveDurationMinutes(),
            $projection->getEffectivePrice(),
            $projection->isActive()
        );
    }
}
