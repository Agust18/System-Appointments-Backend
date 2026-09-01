<?php
namespace App\Controllers\Service;

use App\Services\Service\ServiceFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ServiceGetController extends ResourceController
{
    private ServiceFinderService $serviceFinderService;

    public function __construct()
    {
        $this->serviceFinderService = new ServiceFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $serviceResponse = $this->serviceFinderService->findResponse($id);

        return $this->response->setJSON($serviceResponse);
    }
}