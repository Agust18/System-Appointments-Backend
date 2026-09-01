<?php

namespace App\Controllers\Service;

use App\Services\Service\ServiceDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ServiceDeleteController extends ResourceController
{
    private ServiceDeleterService $serviceDeleterService;

    public function __construct()
    {
        $this->serviceDeleterService = new ServiceDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->serviceDeleterService->delete($id);

        return $this->response->setJSON([
            "id" => $id,
        ]);
    }
}