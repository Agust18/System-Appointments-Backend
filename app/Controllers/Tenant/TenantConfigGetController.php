<?php
namespace App\Controllers\Tenant;

use App\Services\Tenant\TenantConfigFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class TenantConfigGetController extends ResourceController
{
    private TenantConfigFinderService $tenantConfigFinderService;

    public function __construct()
    {
        $this->tenantConfigFinderService = new TenantConfigFinderService();
    }

    public function find(): ResponseInterface
    {
        return $this->response->setJSON($this->tenantConfigFinderService->findResponse());
    }
}
