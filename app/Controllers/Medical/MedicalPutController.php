<?php
namespace App\Controllers\Medical;

use App\Dto\Request\Medical\MedicalUpdateRequest;
use App\Services\Medical\MedicalUpdaterService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalPutController extends ResourceController
{
    private MedicalUpdaterService $medicalUpdaterService;

    public function __construct()
    {
        $this->medicalUpdaterService = new MedicalUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $medicalResponse = $this->medicalUpdaterService->update( $id, $request);
        return $this->response->setJSON($medicalResponse);
    }

    private function getRequest(): MedicalUpdateRequest
    {
        $parameters = $this->request->getJSON();
        
        return new MedicalUpdateRequest(
            $parameters->tuition ?? null,
            $parameters->specialtyId ?? null,
            $parameters->phone ?? null,
            $parameters->isActive ?? null
        );
    }
}