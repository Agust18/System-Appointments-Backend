<?php
namespace App\Controllers\Evolution;

use App\Dto\Request\Evolution\EvolutionAccessLogFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Services\Evolution\Projection\EvolutionAccessLogsProjectionSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionAccessLogsGetController extends ResourceController
{
    private EvolutionAccessLogsProjectionSearcherService $searcherService;

    public function __construct()
    {
        $this->searcherService = new EvolutionAccessLogsProjectionSearcherService();
    }

    public function search(): ResponseInterface
    {
        $responses = $this->searcherService->search($this->getRequest(), (int) session()->get('roleId'));

        return $this->response->setJSON($responses);
    }

    private function getRequest(): EvolutionAccessLogFilterRequest
    {
        RequestValidator::validate($this->request->getGet(), EvolutionAccessLogFilterRequest::rules(), EvolutionAccessLogFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');

        return new EvolutionAccessLogFilterRequest(
            new PaginationRequest($page ? (int) $page : 1, $size ? (int) $size : 10),
            trim((string) $this->request->getGet('search')) ?: null,
            $this->request->getGet('from') ?: null,
            $this->request->getGet('to') ?: null
        );
    }
}
