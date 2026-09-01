<?php
namespace App\Converter\MedicalService;

use App\Dto\Response\MedicalService\MedicalServiceResponse;
use App\Entity\MedicalService\MedicalService;

final class MedicalServiceToMedicalServiceResponseConverter
{
    public function convert(MedicalService $medicalService): MedicalServiceResponse
    {
        return new MedicalServiceResponse(
            $medicalService->getId(),
            $medicalService->getMedicalId(),
            $medicalService->getServiceId(),
            $medicalService->getCustomPrice(),
            $medicalService->getCustomDurationMinutes(),
            $medicalService->isActive(),
            $medicalService->getCreatedAt()?->format('Y-m-d H:i:s'),
            $medicalService->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}