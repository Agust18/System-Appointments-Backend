<?php 

namespace App\Filters;

use App\Services\Auth\TokenValidatorService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class Auth implements FilterInterface {

    public function before(RequestInterface $request, $arguments = null)
    {
        $token = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
        $response = \Config\Services::response();

        if (empty($token)) {
            return $response->setStatusCode(401)->setJSON([
                "message" => "Token no encontrado"
            ]);
        }

        $validatorService = new TokenValidatorService();

        $user = $validatorService->validate($token);

        if (!$user) {
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