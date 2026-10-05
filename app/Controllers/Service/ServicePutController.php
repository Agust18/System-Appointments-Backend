<?php
namespace App\Controllers\Service;

use App\Dto\Request\Service\ServiceUpdateRequest;
use App\Services\Service\ServiceUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ServicePutController extends ResourceController
{
    private ServiceUpdaterService $serviceUpdaterService;

    public function __construct()
    {
        $this->serviceUpdaterService = new ServiceUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $serviceResponse = $this->serviceUpdaterService->update($request, $id);

        return $this->response->setJSON($serviceResponse);
    }

    private function getRequest(): ServiceUpdateRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, ServiceUpdateRequest::rules(), ServiceUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new ServiceUpdateRequest(
            $parameters->name ?? null,
            $parameters->description ?? null,
            $parameters->durationMinutes ?? null,
            $parameters->bufferMinutes ?? null,
            $parameters->price ?? null,
            $parameters->isActive ?? null,
        );
    }
}