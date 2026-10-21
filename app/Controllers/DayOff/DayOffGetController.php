<?php
namespace App\Controllers\DayOff;

use App\Services\DayOff\DayOffFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class DayOffGetController extends ResourceController
{
    private DayOffFinderService $finderService;

    public function __construct()
    {
        $this->finderService = new DayOffFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        return $this->response->setJSON($this->finderService->findResponse($id));
    }
}
