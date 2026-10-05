<?php
namespace App\Controllers\Service;

use App\Dto\Request\Service\ServiceRequest;
use App\Services\Service\ServiceCreatorService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ServicePostController extends ResourceController
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
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, ServiceRequest::rules(), ServiceRequest::messages());

        $parameters = (object) $parameters;

        return new ServiceRequest(

            $parameters->name,
            $parameters->description ?? null,
            $parameters->durationMinutes,
            $parameters->bufferMinutes ?? 0,
            $parameters->price ?? 0.0,
            $parameters->isActive ?? true
        );
    }
}