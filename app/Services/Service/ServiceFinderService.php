<?php
namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Response\Service\ServiceResponse;
use App\Models\Service\ServiceModel;
use Exception;

final class ServiceFinderService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
    }

    public function findResponse(int $id): ServiceResponse
    {
        $service = $this->serviceModel->find($id);
        if (!$service) {
            throw new Exception('Servicio no encontrado');
        }

        return $this->converter->convert($service);
    }
}