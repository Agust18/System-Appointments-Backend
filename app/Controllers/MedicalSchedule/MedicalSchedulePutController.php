<?php
namespace App\Controllers\MedicalSchedule;

use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use App\Services\MedicalSchedule\MedicalScheduleUpdaterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalSchedulePutController extends ResourceController
{
    private MedicalScheduleUpdaterService $updaterService;

    public function __construct()
    {
        $this->updaterService = new MedicalScheduleUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->updaterService->update($request, $id);
        return $this->response->setJSON($response);
    }

    private function getRequest(): MedicalScheduleUpdateRequest
    {
        $parameters = $this->request->getJSON();

        return new MedicalScheduleUpdateRequest(

            $parameters->medicalId ?? null,   
            $parameters->dayOfWeek ?? null,   
            $parameters->startTime ?? null,   
            $parameters->endTime ?? null,     
            $parameters->isActive ?? null     
        );

    }
}