<?php
namespace App\Controllers\DayOff;

use App\Dto\Request\DayOff\DayOffRequest;
use App\Services\DayOff\DayOffCreatorService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class DayOffPostController extends ResourceController
{
    private DayOffCreatorService $creatorService;

    public function __construct()
    {
        $this->creatorService = new DayOffCreatorService();
    }

    public function create(): ResponseInterface
    {
        $response = $this->creatorService->create($this->getRequest());
        return $this->response->setStatusCode(201)->setJSON($response);
    }

    private function getRequest(): DayOffRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, DayOffRequest::rules(), DayOffRequest::messages());

        $parameters = (object) $parameters;

        return new DayOffRequest(
            !empty($parameters->medicalId) ? (int) $parameters->medicalId : null,
            $parameters->dateFrom,
            $parameters->dateTo,
            isset($parameters->reason) && $parameters->reason !== '' ? $parameters->reason : null
        );
    }
}
