<?php

namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Response\Service\ServiceResponse;
use App\Entity\Service\Service;
use App\Exception\Service\ServiceNotFoundException;
use App\Models\Service\ServiceModel;

final class ServiceFinderService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
    }

    public function find(int $id): Service
    {
        $service = $this->serviceModel->find($id);

        if (!$service) {
            throw new ServiceNotFoundException($id);
        }

        return $service;
    }

    public function findResponse(int $id): ServiceResponse
    {
        $service = $this->find($id);
        return $this->converter->convert($service);
    }
}