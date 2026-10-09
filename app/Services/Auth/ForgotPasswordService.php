<?php

namespace App\Services\auth;

use App\Dto\Request\Auth\ForgotPasswordRequest;
use App\Models\User\UserModel;

final class ForgotPasswordService
{
    private UserModel $userModel;
    private SendPasswordResetEmailService $sender;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->sender = new SendPasswordResetEmailService();
    }

    public function requestReset(ForgotPasswordRequest $request): void
    {
        $user = $this->userModel->findByEmail($request->getEmail());

        if (!$user) {
            return;
        }

        $this->sender->send($user);
    }
}