<?php
namespace App\Controllers\Role;

use App\Services\Role\RoleDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class RoleDeleteController extends ResourceController
{
    private RoleDeleterService $roleDeleterService;

    public function __construct()
    {
        $this->roleDeleterService = new RoleDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->roleDeleterService->delete($id);
        return $this->response->setJSON([
            "id" => $id
        ]);
    }
}
