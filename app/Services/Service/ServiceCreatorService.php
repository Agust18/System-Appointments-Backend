<?php

namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Request\Service\ServiceRequest;
use App\Dto\Response\Service\ServiceResponse;
use App\Entity\Service\Service;
use App\Models\Service\ServiceModel;

final class ServiceCreatorService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
    }

    public function create(ServiceRequest $request): ServiceResponse
    {
        $service = Service::fromRequest($request);
        $newService = $this->serviceModel->insert($service);

        return $this->converter->convert($newService);
    }
}
