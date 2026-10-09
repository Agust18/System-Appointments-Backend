<?php

namespace App\Controllers\Platform;

use App\Services\Platform\Tenant\TenantFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /platform/tenants/{id}: detalle de un Tenant (`id`, `slug`, `name`, `status`). 404 si no existe.
 */
final class TenantGetController extends ResourceController
{
    private TenantFinderService $tenantFinderService;

    public function __construct()
    {
        $this->tenantFinderService = new TenantFinderService();
    }

    public function find($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->tenantFinderService->findResponse((int) $id));
    }
}
