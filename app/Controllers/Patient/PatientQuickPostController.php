<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientQuickRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientQuickCreatorService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class PatientQuickPostController extends ResourceController
{
    private PatientQuickCreatorService $patientQuickCreatorService;

    public function __construct()
    {
        $this->patientQuickCreatorService = new PatientQuickCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientQuickRequest = $this->getRequest();
        $patientResponse = $this->patientQuickCreatorService->create($patientQuickRequest);

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientQuickRequest
    {
        $parameters = $this->request->getJSON();

        $userRequest = new UserRequest(
            $parameters->user->username,
            $parameters->user->email,
            $parameters->user->password,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId,
        );

        $patientDataRequest = new PatientDataRequest(
            $parameters->patient->birthDate, 
           
        );

        return new PatientQuickRequest($userRequest, $patientDataRequest);
    }
}