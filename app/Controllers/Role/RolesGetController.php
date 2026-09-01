<?php
namespace App\Controllers\Role;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Role\RoleFilterRequest;
use App\Services\Role\RolesSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class RolesGetController extends ResourceController
{
    private RolesSearcherService $roleSearcherService;

    public function __construct()
    {
        $this->roleSearcherService = new RolesSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->roleSearcherService->search($request);
        return $this->response->setJSON($responses);
    }

    private function getRequest(): RoleFilterRequest
    {

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');

        return new RoleFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            $search ?? null
        );
    }
}