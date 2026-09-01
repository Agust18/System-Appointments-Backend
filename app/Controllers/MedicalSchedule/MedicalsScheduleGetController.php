<?php
namespace App\Controllers\MedicalSchedule;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Services\MedicalSchedule\Projection\MedicalsScheduleProjectionSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalsScheduleGetController extends ResourceController
{
    private MedicalsScheduleProjectionSearcherService $medicalsScheduleSearcherService;

    public function __construct()
    {
        $this->medicalsScheduleSearcherService = new MedicalsScheduleProjectionSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();
        $responses = $this->medicalsScheduleSearcherService->search($request);
        return $this->response->setJSON($responses);
    }

    private function getRequest(): MedicalScheduleFilterRequest
    {
        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $medicalId = $this->request->getGet('medicalId');
        $dayOfWeek = $this->request->getGet('dayOfWeek');
        $isActive = $this->request->getGet('isActive');
        $search = $this->request->getGet('search');

        return new MedicalScheduleFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            $medicalId ? (int) $medicalId : null,
            $dayOfWeek ? (int) $dayOfWeek : null,
            $isActive !== null ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN) : null,
            $search ?? null  
        );
    }
}