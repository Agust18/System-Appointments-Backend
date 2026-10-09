<?php
namespace App\Controllers\Medical;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Services\Medical\Projection\MedicalsProjectionSearcherService;
use App\Validation\RequestValidator;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

final class MedicalsGetController extends ResourceController
{
    private MedicalsProjectionSearcherService $medicalProjectionSearcherService;

    public function __construct()
    {
        $this->medicalProjectionSearcherService = new MedicalsProjectionSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->medicalProjectionSearcherService->searchResponses($request);
        return $this->response->setJSON($responses);
    }



    private function getRequest(): MedicalFilterRequest
    {
        RequestValidator::validate($this->request->getGet(), MedicalFilterRequest::rules(), MedicalFilterRequest::messages());

        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $specialtyId = $this->request->getGet('specialtyId');
        $search = $this->request->getGet('search');

        return new MedicalFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            ($specialtyId !== null && $specialtyId !== '') ? (int) $specialtyId : null,
            $search ?: null
        );
    }
}