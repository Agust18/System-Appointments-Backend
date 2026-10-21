<?php
namespace App\Controllers\DayOff;

use App\Dto\Request\DayOff\DayOffFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Services\DayOff\DaysOffSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class DaysOffGetController extends ResourceController
{
    private DaysOffSearcherService $searcherService;

    public function __construct()
    {
        $this->searcherService = new DaysOffSearcherService();
    }

    public function search(): ResponseInterface
    {
        $responses = $this->searcherService->search(
            $this->getRequest(),
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        );

        return $this->response->setJSON($responses);
    }

    private function getRequest(): DayOffFilterRequest
    {
        RequestValidator::validate($this->request->getGet(), DayOffFilterRequest::rules(), DayOffFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $medicalId = $this->request->getGet('medicalId');

        return new DayOffFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            $medicalId ? (int) $medicalId : null,
            $this->request->getGet('from') ?: null,
            $this->request->getGet('to') ?: null
        );
    }
}
