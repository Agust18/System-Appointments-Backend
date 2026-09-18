<?php

namespace App\Controllers\Auth;

use App\Exception\User\UserNotFoundException;
use App\Services\User\UserFinderByEmailService;
use App\Services\User\UserVerificationAccountService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ResendVerificationPostController extends ResourceController
{
    private UserFinderByEmailService $userFinderService;
    private UserVerificationAccountService $verificationService;

    public function __construct()
    {
        $this->userFinderService = new UserFinderByEmailService();
        $this->verificationService = new UserVerificationAccountService();
    }

    public function resend(): ResponseInterface
    {
        //lee el email del body
        $email = $this->request->getJSON()->email ?? null;

        if (empty($email)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'Email requerido']);
        }

        //busca el user
        try {
            $user = $this->userFinderService->find($email);
        } catch (UserNotFoundException $e) {
            // No revelar si el email existe
            return $this->response->setJSON([
                'message' => 'Si el email existe, te enviamos un link de verificación.',
            ]);
        }

        // Si ya está verificado, no hacer nada
        if ($user->getIsVerified()) {
            return $this->response->setJSON([
                'message' => 'Tu cuenta ya está verificada.',
            ]);
        }

        //reenvia el email
        try {
            $token = $this->verificationService->generateEmailVerificationToken($user->getId());

            // (si tenés el UserEmailVerificationService separado, usalo acá)
            // (si lo unificaste, llamá a sendVerificationEmail($user))
        } catch (\Throwable $e) {
            log_message('error', 'Error reenviando email: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'message' => 'Si el email existe, te enviamos un link de verificación.',
        ]);
    }
}