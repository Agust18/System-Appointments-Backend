<?php
namespace App\Controllers\Service;

use App\Dto\Request\Service\ServiceRequest;
use App\Services\Service\ServiceCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ServicePostController extends ResourceController
{
    private ServiceCreatorService $serviceCreatorService;

    public function __construct()
    {
        $this->serviceCreatorService = new ServiceCreatorService();
    }

    public function create(): ResponseInterface
    {
        $serviceRequest = $this->getRequest();
        $serviceResponse = $this->serviceCreatorService->create($serviceRequest);

        return $this->response->setJSON($serviceResponse);
    }

    private function getRequest(): ServiceRequest
    {
        $parameters = $this->request->getJSON();

        return new ServiceRequest(

            $parameters->name,
            $parameters->description ?? null,
            $parameters->durationMinutes,
            $parameters->bufferMinutes ?? 0,
            $parameters->price ?? 0.0,
            $parameters->slotStepMinutes ?? 15,
            $parameters->isActive ?? true
        );
    }
}