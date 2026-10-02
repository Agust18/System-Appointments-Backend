<?php
namespace App\Services\Service;

use App\Converter\Service\ServiceToServiceResponseConverter;
use App\Dto\Request\Service\ServiceUpdateRequest;
use App\Dto\Response\Service\ServiceResponse;
use App\Models\Service\ServiceModel;
use App\Services\Service\ServiceFinderService;

final class ServiceUpdaterService
{
    private ServiceModel $serviceModel;
    private ServiceToServiceResponseConverter $converter;
    private ServiceFinderService $serviceFinderService;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToServiceResponseConverter();
        $this->serviceFinderService = new ServiceFinderService();

    }

    public function update(ServiceUpdateRequest $request, int $id): ServiceResponse
    {
        $service = $this->serviceFinderService->find($id);

        $service->modify($request);
        $updatedService = $this->serviceModel->update($service);

        return $this->converter->convert($updatedService);
    }
}