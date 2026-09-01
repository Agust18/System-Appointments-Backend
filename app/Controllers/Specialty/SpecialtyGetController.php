<?php
namespace App\Controllers\Specialty;

use App\Services\Specialty\SpecialtyFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class SpecialtyGetController extends ResourceController
{
    private SpecialtyFinderService $specialtyFinderService;

    public function __construct()
    {
        $this->specialtyFinderService = new SpecialtyFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $specialtyResponse = $this->specialtyFinderService->find($id);
        return $this->response->setJSON($specialtyResponse);
    }

    
}