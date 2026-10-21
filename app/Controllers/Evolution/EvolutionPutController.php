<?php
namespace App\Controllers\Evolution;

use App\Dto\Request\Evolution\EvolutionUpdateRequest;
use App\Services\Evolution\EvolutionUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionPutController extends ResourceController
{
    private EvolutionUpdaterService $updaterService;

    public function __construct()
    {
        $this->updaterService = new EvolutionUpdaterService();
    }

    public function do(int $patientId, int $id): ResponseInterface
    {
        $response = $this->updaterService->update(
            $this->getRequest(),
            $patientId,
            $id,
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        );

        return $this->response->setJSON($response);
    }

    private function getRequest(): EvolutionUpdateRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, EvolutionUpdateRequest::rules(), EvolutionUpdateRequest::messages());

        $parameters = (object) $parameters;

        return new EvolutionUpdateRequest(
            $parameters->diagnosis ?? null,
            $parameters->observations ?? null,
            $parameters->treatment ?? null,
            $parameters->indications ?? null
        );
    }
}
