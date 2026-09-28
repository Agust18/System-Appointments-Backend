<?php 

namespace App\Controllers\Auth;

use App\Dto\Request\Auth\UserLoginRequest;
use App\Services\auth\UserLoginService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class UserLoginController extends ResourceController {

    private UserLoginService $userLoginService;

    public function __construct() {
        $this->userLoginService = new UserLoginService();
    }

    public function login(): ResponseInterface
    {
        $request = $this->getRequest();

        $response = $this->userLoginService->login($request);

        return $this->response->setJSON($response);
    }

    private function getRequest(): UserLoginRequest
    {
        $clientRequest = $this->request->getJSON();
        
        return new UserLoginRequest(
            $clientRequest->email,
            $clientRequest->password
        );
    }
}