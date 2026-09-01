<?php

namespace App\Controllers\Medical;

use App\Services\Medical\MedicalDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalDeleteController extends ResourceController
{
    private MedicalDeleterService $medicalDeleterService;

    public function __construct()
    {
        $this->medicalDeleterService = new MedicalDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
         $this->medicalDeleterService->delete($id);

        return $this->response->setJSON([
            "id"  => $id,
            
        ]);
    }
}
