<?php
namespace App\Converter\Service;

use App\Dto\Response\Service\PublicServiceResponse;
use App\Entity\Service\Service;

final class ServiceToPublicServiceResponseConverter
{
    public function convert(Service $service): PublicServiceResponse
    {
        return new PublicServiceResponse(
            $service->getId(),
            $service->getName(),
            $service->getDescription(),
            $service->getDurationMinutes(),
            $service->getPrice()
        );
    }
}
