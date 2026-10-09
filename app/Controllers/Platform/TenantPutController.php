<?php

namespace App\Controllers\Platform;

use App\Dto\Request\Platform\TenantUpdateRequest;
use App\Services\Platform\Tenant\TenantUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;


final class TenantPutController extends ResourceController
{
    private TenantUpdaterService $tenantUpdaterService;

    public function __construct()
    {
        $this->tenantUpdaterService = new TenantUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $response = $this->tenantUpdaterService->update($this->getRequest(), $id);

        return $this->response->setJSON($response);
    }

    private function getRequest(): TenantUpdateRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, TenantUpdateRequest::rules(), TenantUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new TenantUpdateRequest(
            $parameters->name ?? null,
            $parameters->status ?? null
        );
    }
}
