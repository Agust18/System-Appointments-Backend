<?php 

namespace App\Filters;

use App\Exception\Auth\TokenNotFoundException;
use App\Services\Auth\TokenValidatorService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class Auth implements FilterInterface {

    public function before(RequestInterface $request, $arguments = null)
    {
        $token = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
        

        if (empty($token)) {
            throw new TokenNotFoundException();
        }

        $validatorService = new TokenValidatorService();

        $user = $validatorService->validate($token);

        if (!$user) {
            $response = \Config\Services::response();
            return $response->setStatusCode(401)->setJSON([
                "message" => "Token invalido o expirado"
            ]);
        }

        session()->set('userId', $user->getId());
        session()->set('roleId', $user->getRoleId());
    
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {

    }
}