<?php
namespace App\Controllers\Role;

use App\Services\Role\RoleFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class RoleGetController extends ResourceController
{
    private RoleFinderService $roleFinderService;

    public function __construct()
    {
        $this->roleFinderService = new RoleFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $response = $this->roleFinderService->findResponse($id);
        return $this->response->setJSON($response);
    }
}