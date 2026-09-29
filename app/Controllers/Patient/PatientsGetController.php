<?php 
namespace App\Controllers\Patient;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Services\Patient\PatientFilterBuilderService;
use App\Services\Patient\Projection\PatientsProjectionSearcherService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

final class PatientsGetController extends ResourceController
{
    private PatientsProjectionSearcherService $patientProjectionSearcherService;
    private PatientFilterBuilderService $filterBuilderService;

    public function __construct()
    {
        $this->patientProjectionSearcherService = new PatientsProjectionSearcherService();
        $this->filterBuilderService = new PatientFilterBuilderService();
    }

    public function search(): ResponseInterface 
    {
        $request = $this->getRequest();
        $responses = $this->patientProjectionSearcherService->searchResponses($request);
        return $this->response->setJSON($responses);
    }


    private function getRequest(): PatientFilterRequest
    {
        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $search = $this->request->getGet('search');

        return $this->filterBuilderService->build(
            new PaginationRequest(
                $page ? (int)$page : 1,
                $size ? (int)$size : 10,
            ),
            $search ?? null
        );
    }
}