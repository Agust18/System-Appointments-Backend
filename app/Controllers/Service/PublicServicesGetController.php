<?php
namespace App\Controllers\Service;

use App\Services\Service\PublicServicesSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /public/services (sin auth). Solo servicios activos; el cliente no controla
 * el filtro isActive ni la paginación.
 */
final class PublicServicesGetController extends ResourceController
{
    private PublicServicesSearcherService $searcher;

    public function __construct()
    {
        $this->searcher = new PublicServicesSearcherService();
    }

    public function search(): ResponseInterface
    {
        return $this->response->setJSON($this->searcher->searchResponses());
    }
}
