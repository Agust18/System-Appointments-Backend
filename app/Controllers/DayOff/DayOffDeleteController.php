<?php
namespace App\Controllers\DayOff;

use App\Services\DayOff\DayOffDeleterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class DayOffDeleteController extends ResourceController
{
    private DayOffDeleterService $deleterService;

    public function __construct()
    {
        $this->deleterService = new DayOffDeleterService();
    }

    public function do(int $id): ResponseInterface
    {
        $this->deleterService->delete($id);
        return $this->response->setJSON(["id" => $id]);
    }
}
