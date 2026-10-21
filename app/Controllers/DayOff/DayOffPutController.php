<?php
namespace App\Controllers\DayOff;

use App\Dto\Request\DayOff\DayOffUpdateRequest;
use App\Services\DayOff\DayOffUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class DayOffPutController extends ResourceController
{
    private DayOffUpdaterService $updaterService;

    public function __construct()
    {
        $this->updaterService = new DayOffUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $response = $this->updaterService->update($this->getRequest(), $id);
        return $this->response->setJSON($response);
    }

    private function getRequest(): DayOffUpdateRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, DayOffUpdateRequest::rules(), DayOffUpdateRequest::messages());

        $medicalIdPresent = array_key_exists('medicalId', $parameters);
        $reasonPresent = array_key_exists('reason', $parameters);

        $parameters = (object) $parameters;

        return new DayOffUpdateRequest(
            !empty($parameters->medicalId) ? (int) $parameters->medicalId : null,
            $parameters->dateFrom ?? null,
            $parameters->dateTo ?? null,
            isset($parameters->reason) && $parameters->reason !== '' ? $parameters->reason : null,
            $medicalIdPresent,
            $reasonPresent
        );
    }
}
