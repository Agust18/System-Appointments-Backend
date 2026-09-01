<?php

namespace App\Controllers\Patient;

use App\Services\Patient\PatientDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PatientDeleteController extends ResourceController
{
    private PatientDeleterService $patientDeleterService;

    public function __construct()
    {
        $this->patientDeleterService = new PatientDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
         $this->patientDeleterService->delete($id);

        return $this->response->setJSON([
            "id"  => $id,
           
        ]);
    }
}
