<?php
namespace App\Converter\Service;

use App\Dto\Response\Service\ServiceResponse;
use App\Entity\Service\Service;

final class ServiceToServiceResponseConverter
{
    public function convert(Service $service): ServiceResponse
    {
        return new ServiceResponse(
            $service->getId(),
            $service->getName(),
            $service->getDescription(),
            $service->getDurationMinutes(),
            $service->getBufferMinutes(),
            $service->getPrice(),
            $service->getSlotStepMinutes(),
            $service->isActive(),
            $service->getCreatedAt()?->format('Y-m-d H:i:s'),
            $service->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
