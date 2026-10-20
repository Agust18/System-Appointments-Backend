<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Auth\ResendVerificationEmailRequest;
use App\Services\Auth\ResendVerificationEmailService;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ResendVerificationPostController extends ResourceController
{
    private ResendVerificationEmailService $service;

    public function __construct()
    {
        $this->service = new ResendVerificationEmailService();
    }

    public function resend(): ResponseInterface
    {
        $request = $this->getRequest();
        
       
        $this->service->resend($request);

        return $this->response->setJSON([
            'message' => 'Si el email corresponde a una cuenta pendiente, recibirás un nuevo enlace.',
        ]);
    }

    private function getRequest(): ResendVerificationEmailRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];

        RequestValidator::validate($parameters, ResendVerificationEmailRequest::rules(), ResendVerificationEmailRequest::messages());

        $parameters = (object) $parameters;

        return new ResendVerificationEmailRequest(
            email: (string) ($parameters->email ?? '')
        );
    }
}