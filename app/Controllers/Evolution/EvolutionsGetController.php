<?php
namespace App\Controllers\Evolution;

use App\Dto\Request\PaginationRequest;
use App\Services\Evolution\EvolutionsSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionsGetController extends ResourceController
{
    private EvolutionsSearcherService $searcherService;

    public function __construct()
    {
        $this->searcherService = new EvolutionsSearcherService();
    }

    public function search(int $patientId): ResponseInterface
    {
        $responses = $this->searcherService->search(
            $patientId,
            $this->getPagination(),
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        );

        return $this->response->setJSON($responses);
    }

    private function getPagination(): PaginationRequest
    {
        RequestValidator::validate($this->request->getGet(), PaginationRequest::rules(), PaginationRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');

        return new PaginationRequest($page ? (int) $page : 1, $size ? (int) $size : 10);
    }
}
