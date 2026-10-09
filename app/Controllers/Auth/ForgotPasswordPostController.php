<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Auth\ForgotPasswordRequest;
use App\Services\auth\ForgotPasswordService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ForgotPasswordPostController extends ResourceController
{
    private ForgotPasswordService $forgotPasswordService;

    public function __construct()
    {
        $this->forgotPasswordService = new ForgotPasswordService();
    }

    public function requestReset(): ResponseInterface
    {
        $forgotPasswordRequest = $this->getRequest();
        
        $this->forgotPasswordService->requestReset($forgotPasswordRequest);

        return $this->response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
    }

    private function getRequest(): ForgotPasswordRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, ForgotPasswordRequest::rules(), ForgotPasswordRequest::messages());

        $parameters = (object) $parameters;

        return new ForgotPasswordRequest(
            email: (string) ($parameters->email ?? '')
        );
    }
}