<?php
namespace App\Controllers\Service;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Services\Service\ServicesSearcherService;
use App\Validation\RequestValidator;
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
        RequestValidator::validate($this->request->getGet(), ServiceFilterRequest::rules(), ServiceFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');
        $isActive = $this->request->getGet('isActive');

        return new ServiceFilterRequest(
            new PaginationRequest(
                $page ? (int)$page : 1,
                $size ? (int)$size : 10,
            ),
            $search ?? null,
            ($isActive !== null && $isActive !== '') ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN) : null,
        );
    }
}