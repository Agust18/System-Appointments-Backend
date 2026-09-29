<?php

namespace App\Controllers\User;

use CodeIgniter\HTTP\ResponseInterface;
use App\Dto\Request\User\UserRequest;
use App\Services\User\UserUpdaterService;
use App\Validation\RequestValidator;
use CodeIgniter\RESTful\ResourceController;
use App\Dto\Request\User\UserUpdaterRequest;

final class UserPutController extends ResourceController
{
    private UserUpdaterService $userUpdaterService;

    public function __construct()
    {
        $this->userUpdaterService = new UserUpdaterService();
    }

    public function do(int $id): ResponseInterface
    {
        $request = $this->getRequest();
        $response = $this->userUpdaterService->update($request, $id);

        return $this->response->setJSON($response);
    }

    public function getRequest(): UserUpdaterRequest
    {
        $request = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($request, UserUpdaterRequest::rules(), UserUpdaterRequest::messages());

        $request = (object) $request;

    return new UserUpdaterRequest(
        $request->firstName ?? null,
        $request->lastName ?? null,
        $request->email ?? null,
        $request->password ?? null,
        $request->dni ?? null,
        $request->isActive ?? null,
        $request->roleId ?? null
    );

            
    
    }
}
