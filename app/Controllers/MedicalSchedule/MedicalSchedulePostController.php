<?php
namespace App\Controllers\MedicalSchedule;

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Services\MedicalSchedule\MedicalScheduleCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalSchedulePostController extends ResourceController
{
    private MedicalScheduleCreatorService $creatorService;

    public function __construct()
    {
        $this->creatorService = new MedicalScheduleCreatorService();
    }

    public function create(): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->creatorService->create($request);
        return $this->response->setJSON($response);
    }

    private function getRequest(): MedicalScheduleRequest
    {
        $parameters = $this->request->getJSON();
        
        return new MedicalScheduleRequest(
        $parameters->medicalId,     
        $parameters->dayOfWeek,     
        $parameters->startTime,     
        $parameters->endTime,       
        $parameters->isActive ?? true
    );
    }
}