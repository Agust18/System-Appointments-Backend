<?php
// app/Services/Service/Validations/ServiceCreateValidatorService.php

namespace App\Services\Service\Validations;

use App\Dto\Request\Service\ServiceRequest;
use App\Exception\Service\ServiceAlreadyExistsException;
use App\Models\Service\ServiceModel;

final class ServiceCreateValidatorService
{
    private ServiceModel $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

    public function validate(ServiceRequest $request): void
    {
        $existingService = $this->serviceModel->findByName($request->getName());

        if ($existingService) {
            throw new ServiceAlreadyExistsException($request->getName());
        }
    }
}