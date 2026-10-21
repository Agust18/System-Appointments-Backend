<?php
namespace App\Controllers\Evolution;

use App\Services\Evolution\EvolutionSignerService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionSignPostController extends ResourceController
{
    private EvolutionSignerService $signerService;

    public function __construct()
    {
        $this->signerService = new EvolutionSignerService();
    }

    public function do(int $patientId, int $id): ResponseInterface
    {
        return $this->response->setJSON($this->signerService->sign(
            $patientId,
            $id,
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        ));
    }
}
