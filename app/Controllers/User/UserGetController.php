<?php

namespace App\Controllers\User;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\User\UserFinderService;

final class UserGetController extends ResourceController
{

    private UserFinderService $UserFinderService;

    public function __construct()
    {
        $this->UserFinderService = new UserFinderService();
    }
    public function find(int $id): ResponseInterface
    {

        $user = $this->UserFinderService->findResponse($id);
        return $this->response->setJSON($user);
    }
}
