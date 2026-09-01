<?php

namespace App\Controllers\Specialty;

use App\Services\Specialty\SpecialtyDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class SpecialtyDeleteController extends ResourceController
{
    private SpecialtyDeleterService $specialtyDeleterService;

    public function __construct()
    {
        $this->specialtyDeleterService = new SpecialtyDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->specialtyDeleterService->delete($id);

        return $this->response->setJSON([
            "id"  => $id,
            
        ]);
    }
}
