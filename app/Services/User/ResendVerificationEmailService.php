<?php

namespace App\Services\User;

use App\Dto\Request\Auth\ResendVerificationEmailRequest;
use App\Models\User\UserModel;

final class ResendVerificationEmailService
{
    private UserModel $userModel;
    private SendVerificationEmailService $sender;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->sender = new SendVerificationEmailService();
    }

    public function resend(ResendVerificationEmailRequest $request): void
    {
        $user = $this->userModel->findByEmail($request->getEmail());

        if (!$user || $user->getIsVerified()) {
            return;
        }

        $this->sender->send($user);
    }
}