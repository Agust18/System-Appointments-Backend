<?php
namespace App\Controllers\Evolution;

use App\Dto\Request\Evolution\EvolutionRequest;
use App\Services\Evolution\EvolutionCreatorService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class EvolutionPostController extends ResourceController
{
    private EvolutionCreatorService $creatorService;

    public function __construct()
    {
        $this->creatorService = new EvolutionCreatorService();
    }

    /** `$patientId` es opcional solo para ser compatible con `ResourceController::create()`; la ruta siempre lo envía. */
    public function create($patientId = null): ResponseInterface
    {
        $response = $this->creatorService->create(
            $this->getRequest(),
            (int) $patientId,
            (int) session()->get('roleId'),
            (int) session()->get('userId')
        );

        return $this->response->setStatusCode(201)->setJSON($response);
    }

    private function getRequest(): EvolutionRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, EvolutionRequest::rules(), EvolutionRequest::messages());

        $parameters = (object) $parameters;

        return new EvolutionRequest(
            (int) $parameters->appointmentId,
            $this->nullIfEmpty($parameters->diagnosis ?? null),
            $this->nullIfEmpty($parameters->observations ?? null),
            $this->nullIfEmpty($parameters->treatment ?? null),
            $this->nullIfEmpty($parameters->indications ?? null),
            !empty($parameters->correctsId) ? (int) $parameters->correctsId : null
        );
    }

    private function nullIfEmpty(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? $value : null;
    }
}
