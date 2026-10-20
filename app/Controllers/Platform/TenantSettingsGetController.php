<?php

namespace App\Controllers\Platform;

use App\Services\Platform\Tenant\TenantSettingsFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;


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
