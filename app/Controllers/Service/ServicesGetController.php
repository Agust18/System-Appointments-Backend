<?php
namespace App\Controllers\Service;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Services\Service\ServicesSearcherService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

final class ServicesGetController extends ResourceController
{
    private ServicesSearcherService $servicesSearcherService;

    public function __construct()
    {
        $this->servicesSearcherService = new ServicesSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->servicesSearcherService->searchResponses($request);

        return $this->response->setJSON($responses);
    }

    private function getRequest(): ServiceFilterRequest
    {
        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');
        $isActive = $this->request->getGet('is_active');

        return new ServiceFilterRequest(
            new PaginationRequest(
                $page ? (int)$page : 1,
                $size ? (int)$size : 10,
            ),
            $search ?? null,
            $isActive !== null ? (bool)$isActive : null,
        );
    }
}