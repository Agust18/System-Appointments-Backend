<?php

namespace App\Controllers\User;

use App\Dto\Request\User\UserRequest;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Services\User\UserCreatorService;


final class UserPostController extends ResourceController
{
    private UserCreatorService  $userCreatorService;

    public function __construct()
    {
        $this->userCreatorService = new UserCreatorService();
    }

    public function create(): ResponseInterface
    {
        $userRequest = $this->getRequest();
        $userResponse = $this->userCreatorService->create($userRequest, true);


        return $this->response->setJSON($userResponse);
    }

    private function getRequest(): UserRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, UserRequest::rules(), UserRequest::messages());

        $parameters = (object) $parameters;

        return new UserRequest(
            $parameters->firstName,
            $parameters->lastName,
            $parameters->email,
            $parameters->phone ?? null,
            $parameters->password,
            $parameters->dni,
            $parameters->isActive ?? true,
            $parameters->roleId
        );
    }
}
