<?php
namespace App\Controllers\Medical;

use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalQuickRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Medical\MedicalQuickCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MedicalQuickPostController extends ResourceController
{
    private MedicalQuickCreatorService $medicalQuickCreatorService;

    public function __construct()
    {
        $this->medicalQuickCreatorService = new MedicalQuickCreatorService();
    }

    public function create(): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->medicalQuickCreatorService->create($request);

        return $this->response->setJSON($response);
    }

    private function getRequest(): MedicalQuickRequest
    {
        $parameters = $this->request->getJSON();

        $userRequest = new UserRequest(
            $parameters->user->username,
            $parameters->user->email,
            $parameters->user->password,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId
        );

        $medicalDataRequest = new MedicalDataRequest(
            tuition: $parameters->medical->tuition,
            specialtyId: $parameters->medical->specialtyId,
            phone: $parameters->medical->phone ?? null,
            isActive: $parameters->medical->isActive ?? true
        );

        return new MedicalQuickRequest($userRequest, $medicalDataRequest);
    }
}