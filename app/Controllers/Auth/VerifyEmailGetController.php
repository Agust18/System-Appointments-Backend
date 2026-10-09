<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Auth\VerifyEmailRequest;
use App\Services\Auth\VerifyEmailService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class VerifyEmailGetController extends ResourceController
{
    private VerifyEmailService $service;

    public function __construct()
    {
        $this->service = new VerifyEmailService();
    }

    public function verify(): ResponseInterface
    {
        $request = $this->getRequest();

        $this->service->verify($request);

        return $this->response->setJSON(['message' => 'Email verificado correctamente.']);
    }

    private function getRequest(): VerifyEmailRequest
    {
        $token = $this->request->getGet('token');

        RequestValidator::validate(['token' => $token], VerifyEmailRequest::rules(), VerifyEmailRequest::messages());

        return new VerifyEmailRequest(
            token: (string) ($token ?? '')
        );
    }
}