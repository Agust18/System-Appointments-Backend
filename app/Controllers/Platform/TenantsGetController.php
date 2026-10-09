<?php

namespace App\Controllers\Platform;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Platform\TenantFilterRequest;
use App\Services\Platform\Tenant\TenantsSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class TenantsGetController extends ResourceController
{
    private TenantsSearcherService $tenantsSearcherService;

    public function __construct()
    {
        $this->tenantsSearcherService = new TenantsSearcherService();
    }

    public function search(): ResponseInterface
    {
        $responses = $this->tenantsSearcherService->searchResponses($this->getRequest());

        return $this->response->setJSON($responses);
    }

    private function getRequest(): TenantFilterRequest
    {
        RequestValidator::validate($this->request->getGet(), TenantFilterRequest::rules(), TenantFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');

        return new TenantFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            ($search !== null && $search !== '') ? $search : null,
            ($status !== null && $status !== '') ? $status : null
        );
    }
}
