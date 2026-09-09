<?php 

namespace App\Controllers\User;

use App\Dto\Request\User\UserLoginRequest;
use App\Services\User\UserLoginService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class UserLoginController extends ResourceController {

    private UserLoginService $userLoginService;

    public function __construct() {
        $this->userLoginService = new UserLoginService();
    }

    public function login(): ResponseInterface
    {
        header('Access-Control-Allow-Origin: https://deploy-preview-5--tusturnos.netlify.app');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
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