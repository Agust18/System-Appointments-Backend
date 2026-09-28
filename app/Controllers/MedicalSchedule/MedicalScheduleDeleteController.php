<?php
namespace App\Controllers\MedicalSchedule;

use App\Services\MedicalSchedule\MedicalScheduleDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalScheduleDeleteController extends ResourceController
{
    private MedicalScheduleDeleterService $deleterService;

    public function __construct()
    {
        $this->deleterService = new MedicalScheduleDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->deleterService->delete($id);
        return $this->response->setJSON([
            "id" => $id,
            
        ]);
    }
}