<?php

namespace App\Controllers\Medical;

use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Services\Medical\Projection\MedicalAvailableSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalAvailableGetController extends ResourceController
{
    private MedicalAvailableSearcherService $searcher;

    public function __construct()
    {
        $this->searcher = new MedicalAvailableSearcherService();
    }

    public function search(): ResponseInterface
    {
        RequestValidator::validate($this->request->getGet(), MedicalFilterRequest::rules(), MedicalFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $specialtyId = $this->request->getGet('specialtyId');
        $search = $this->request->getGet('search');

        $request = new MedicalFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            ($specialtyId !== null && $specialtyId !== '') ? (int) $specialtyId : null,
            $search ?: null
        );

        return $this->response->setJSON($this->searcher->searchResponses($request));
    }
}
