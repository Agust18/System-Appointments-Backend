<?php

namespace App\Controllers\User;

use App\Services\User\UserDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class UserDeleteController extends ResourceController
{
    private UserDeleterService $userDeleterService;

    public function __construct()
    {
        $this->userDeleterService = new UserDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->userDeleterService->delete($id);
        
        return $this->response->setJSON([
            "id"  => $id
        ]);
    }
}
