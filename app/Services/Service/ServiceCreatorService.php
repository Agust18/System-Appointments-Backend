<?php

namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Request\Service\ServiceRequest;
use App\Dto\Response\Service\ServiceResponse;
use App\Entity\Service\Service;
use App\Models\Service\ServiceModel;
use App\Services\Service\Validations\ServiceCreateValidatorService;

final class ServiceCreatorService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;
    private ServiceCreateValidatorService $validator;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
        $this->validator = new ServiceCreateValidatorService();
    }

    public function create(ServiceRequest $request): ServiceResponse
    {
        $this->validator->validate($request);
     
        $service = Service::fromRequest($request);
        
        $newService = $this->serviceModel->insert($service);

        return $this->converter->convert($newService);
    }
}
