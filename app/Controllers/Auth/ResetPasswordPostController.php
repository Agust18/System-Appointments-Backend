<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Auth\ResetPasswordRequest;
use App\Services\Auth\ResetPasswordService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ResetPasswordPostController extends ResourceController
{
    private ResetPasswordService $resetPasswordService;

    public function __construct()
    {
        $this->resetPasswordService = new ResetPasswordService();
    }

    public function reset(): ResponseInterface
    {
        $resetPasswordRequest = $this->getRequest();
        
        
        $this->resetPasswordService->reset($resetPasswordRequest);

        return $this->response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
    }

    private function getRequest(): ResetPasswordRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, ResetPasswordRequest::rules(), ResetPasswordRequest::messages());

        $parameters = (object) $parameters;

        return new ResetPasswordRequest(
            token: (string) ($parameters->token ?? ''),
            password: (string) ($parameters->password ?? '')
        );
    }
}