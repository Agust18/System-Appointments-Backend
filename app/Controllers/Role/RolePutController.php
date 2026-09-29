<?php
namespace App\Controllers\Role;

use App\Dto\Request\Role\RoleUpdateRequest;
use App\Services\Role\RoleUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class RolePutController extends ResourceController
{
    private RoleUpdaterService $roleUpdaterService;

    public function __construct()
    {
        $this->roleUpdaterService = new RoleUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->roleUpdaterService->update($request, $id);
        return $this->response->setJSON($response);
    }

    private function getRequest(): RoleUpdateRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, RoleUpdateRequest::rules(), RoleUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new RoleUpdateRequest(
            $parameters->name ?? null
        );
    }
}