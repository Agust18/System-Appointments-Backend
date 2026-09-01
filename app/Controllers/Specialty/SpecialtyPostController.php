<?php
namespace App\Controllers\Specialty;

use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Services\Specialty\SpecialtyCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class SpecialtyPostController extends ResourceController
{
    private SpecialtyCreatorService $specialtyCreatorService;

    public function __construct()
    {
        $this->specialtyCreatorService = new SpecialtyCreatorService();
    }

    public function create(): ResponseInterface
    {
        $specialtyRequest = $this->getRequest();
        $specialtyResponse = $this->specialtyCreatorService->create($specialtyRequest);
        
        return $this->response->setJSON($specialtyResponse);
    }

    private function getRequest(): SpecialtyRequest
    {
        $parameters = $this->request->getJSON();
        
        return new SpecialtyRequest(
            $parameters->name,
            $parameters->description ?? null
        );
    }
}