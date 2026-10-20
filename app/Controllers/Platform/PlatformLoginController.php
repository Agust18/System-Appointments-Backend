<?php

namespace App\Controllers\Platform;

use App\Dto\Request\Platform\PlatformLoginRequest;
use App\Services\Platform\PlatformLoginService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PlatformLoginController extends ResourceController
{
    private PlatformLoginService $loginService;

    public function __construct()
    {
        $this->loginService = new PlatformLoginService();
    }

    public function login(): ResponseInterface
    {
        $response = $this->loginService->login($this->getRequest());

        return $this->response->setJSON($response);
    }

    private function getRequest(): PlatformLoginRequest
    {
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PlatformLoginRequest::rules(), PlatformLoginRequest::messages());

        $parameters = (object) $parameters;

        return new PlatformLoginRequest(
            $parameters->email,
            $parameters->password
        );
    }
}
