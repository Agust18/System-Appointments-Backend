<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPostController extends ResourceController
{
    private PatientCreatorService $patientCreatorService;

    public function __construct()
    {
        $this->patientCreatorService = new PatientCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientRequest = $this->getRequest();
        $patientResponse = $this->patientCreatorService->create($patientRequest, true);

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientRequest
    {
        $parameters = $this->request->getJSON();

        $userRequest = new UserRequest(
            $parameters->user->firstName,
            $parameters->user->lastName,
            $parameters->user->email,
            $parameters->user->password,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId ?? null,
        );

        $patientDataRequest = new PatientDataRequest(
            $parameters->patient->birthDate,
        );

        return new PatientRequest($userRequest, $patientDataRequest);
    }
}
