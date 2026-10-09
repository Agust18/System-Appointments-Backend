<?php
namespace App\Controllers\MedicalSchedule;

use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use App\Services\MedicalSchedule\MedicalScheduleUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalSchedulePutController extends ResourceController
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
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, MedicalScheduleUpdateRequest::rules(), MedicalScheduleUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new MedicalScheduleUpdateRequest(

            $parameters->medicalId ?? null,   
            $parameters->dayOfWeek ?? null,   
            $parameters->startTime ?? null,   
            $parameters->endTime ?? null,     
            $parameters->isActive ?? null     
        );

    }
}