<?php
namespace App\Converter\Medical;

use App\Dto\Response\Medical\MedicalResponse;
use App\Entity\Medical\Medical;

final class MedicalToMedicalResponseConverter
{
    public function convert(Medical $medical): MedicalResponse
    {
        return new MedicalResponse(
            $medical->getId(),
            $medical->getUserId(),
            $medical->getTuition(),
            $medical->getSpecialtyId(),
            $medical->getPhone(),
            $medical->getIsActive()
        );
    }
}