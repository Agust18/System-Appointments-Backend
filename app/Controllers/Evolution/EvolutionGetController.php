<?php
namespace App\Controllers\Evolution;

use App\Services\Evolution\EvolutionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionGetController extends ResourceController
{
    private EvolutionFinderService $finderService;

    public function __construct()
    {
        $this->finderService = new EvolutionFinderService();
    }

    public function find(int $patientId, int $id): ResponseInterface
    {
        return $this->response->setJSON($this->finderService->findResponse(
            $patientId,
            $id,
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        ));
    }
}
