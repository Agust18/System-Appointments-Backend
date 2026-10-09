<?php

namespace App\Controllers\Platform;

use App\Dto\Request\Platform\TenantSettingsUpdateRequest;
use App\Services\Platform\Tenant\TenantSettingsUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;


final class TenantSettingsPutController extends ResourceController
{
    private TenantSettingsUpdaterService $tenantSettingsUpdaterService;

    public function __construct()
    {
        $this->tenantSettingsUpdaterService = new TenantSettingsUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $response = $this->tenantSettingsUpdaterService->update($this->getRequest(), $id);

        return $this->response->setJSON($response);
    }

    private function getRequest(): TenantSettingsUpdateRequest
    {
        $parameters = TenantSettingsUpdateRequest::sanitize($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, TenantSettingsUpdateRequest::rules(), TenantSettingsUpdateRequest::messages());

        return TenantSettingsUpdateRequest::fromArray($parameters);
    }
}
