<?php

namespace App\Controllers\Auth;

use App\Exception\User\InvalidVerificationTokenException;
use App\Exception\User\UserAlreadyVerifiedException;
use App\Services\User\UserVerificationAccountService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class VerifyEmailGetController extends ResourceController
{
    private UserVerificationAccountService $verificationService;

    public function __construct()
    {
        $this->verificationService = new UserVerificationAccountService();
    }

    public function verify(): ResponseInterface
    {
        // lee el token de la URL (?token=xxx)
        $token = $this->request->getGet('token');

        // valida que venga
        if (empty($token)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'Token requerido']);
        }

        // verificar
        try {
            $this->verificationService->verifyEmail($token);

            return $this->response->setJSON([
                'message' => 'Cuenta verificada correctamente.',
            ]);
        } catch (UserAlreadyVerifiedException $e) {
            return $this->response->setJSON([
                'message' => 'Tu cuenta ya estaba verificada.',
            ]);
        } catch (InvalidVerificationTokenException $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }
}