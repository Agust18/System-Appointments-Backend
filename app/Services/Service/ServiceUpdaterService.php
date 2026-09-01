<?php
namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Request\Service\ServiceUpdateRequest;
use App\Dto\Response\Service\ServiceResponse;
use App\Models\Service\ServiceModel;
use Exception;

final class ServiceUpdaterService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
    }

    public function update(ServiceUpdateRequest $request, int $id): ServiceResponse
    {
        $service = $this->serviceModel->find($id);
        if (!$service) {
            throw new Exception('Servicio no encontrado');
        }

        $service->modify($request);
        $updatedService = $this->serviceModel->update($service);

        return $this->converter->convert($updatedService);
    }
}