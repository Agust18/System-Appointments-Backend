<?php
namespace App\Converter\Specialty;

use App\Dto\Response\Specialty\SpecialtyResponse;
use App\Entity\Specialty\Specialty;

final class SpecialtyToSpecialtyResponseConverter
{
    public function convert(Specialty $specialty): SpecialtyResponse
    {
        return new SpecialtyResponse(
            $specialty->getId(),
            $specialty->getName(),
            $specialty->getDescription(),
            $specialty->getCreatedAt()?->format('Y-m-d H:i:s'),
            $specialty->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}