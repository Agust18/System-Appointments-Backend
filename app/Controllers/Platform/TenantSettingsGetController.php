<?php

namespace App\Controllers\Platform;

use App\Services\Platform\Tenant\TenantSettingsFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /platform/tenants/{id}/settings: configuración completa del Tenant (datos internos incluidos).
 */
final class TenantSettingsGetController extends ResourceController
{
    private TenantSettingsFinderService $tenantSettingsFinderService;

    public function __construct()
    {
        $this->tenantSettingsFinderService = new TenantSettingsFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        return $this->response->setJSON($this->tenantSettingsFinderService->findResponse($id));
    }
}
