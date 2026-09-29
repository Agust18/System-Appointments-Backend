<?php
namespace App\Controllers\Medical;

use App\Dto\Request\Medical\MedicalDataRequest;
use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Medical\MedicalCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class MedicalPostController extends ResourceController
{
    private MedicalCreatorService $medicalCreatorService;

    public function __construct()
    {
        $this->medicalCreatorService = new MedicalCreatorService();
    }

    public function create(): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->medicalCreatorService->create($request);

        return $this->response->setJSON($response);
    }

    private function getRequest(): MedicalRequest
    {
        $parameters = $this->request->getJSON();

        $userRequest = new UserRequest(
            $parameters->user->username,
            $parameters->user->email,
            $parameters->user->password,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId ?? null
        );

        $medicalDataRequest = new MedicalDataRequest(
            tuition: $parameters->medical->tuition,
            specialtyId: $parameters->medical->specialtyId,
            phone: $parameters->medical->phone ?? null,
            isActive: $parameters->medical->isActive ?? true
        );

        return new MedicalRequest($userRequest, $medicalDataRequest);
    }
}
