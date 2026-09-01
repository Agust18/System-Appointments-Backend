<?php
namespace App\Controllers\Specialty;

use App\Dto\Request\PaginationRequest;

use App\Dto\Request\Specialty\SpecialtyFilterRequest;

use App\Services\Specialty\SpecialtiesSearcherService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

final class SpecialtiesGetController extends ResourceController
{
    private SpecialtiesSearcherService $specialtySearcherService;

    public function __construct()
    {
        $this->specialtySearcherService = new SpecialtiesSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->specialtySearcherService->searchResponses($request);
        return $this->response->setJSON($responses);
    }

    private function getRequest(): SpecialtyFilterRequest
    {
        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');

        return new SpecialtyFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            $search ?? null
        );
    }
}