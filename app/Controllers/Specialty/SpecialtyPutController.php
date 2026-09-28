<?php
namespace App\Controllers\Specialty;

use App\Dto\Request\Specialty\SpecialtyUpdateRequest;
use App\Services\Specialty\SpecialtyUpdaterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class SpecialtyPutController extends ResourceController
{
    private SpecialtyUpdaterService $specialtyUpdaterService;

    public function __construct()
    {
        $this->specialtyUpdaterService = new SpecialtyUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $specialtyResponse = $this->specialtyUpdaterService->update($request, $id);
        return $this->response->setJSON($specialtyResponse);
    }

    private function getRequest(): SpecialtyUpdateRequest
    {
        $parameters = $this->request->getJSON();
        
        return new SpecialtyUpdateRequest(
            $parameters->name ?? null,
            $parameters->description ?? null,
        
        );
    }
}