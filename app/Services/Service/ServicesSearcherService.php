<?php
namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Dto\Response\Service\ServiceResponse;
use App\Models\Service\ServiceModel;

final class ServicesSearcherService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
    }

    /**
     * @return ServiceResponse[]
     */
    public function searchResponses(ServiceFilterRequest $request): array
    {
        $entities = $this->serviceModel->search($request);

        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }

        return $responses;
    }
}