<?php

namespace App\Services\auth;

use App\Dto\Request\Auth\ResendVerificationEmailRequest;
use App\Exception\Auth\AlreadyVerifiedException;
use App\Services\User\UserFinderByEmailService;

final class ResendVerificationEmailService
{
   
    private SendVerificationEmailService $sender;

    private UserFinderByEmailService $userFinderByEmailService;

    public function __construct()
    {
        $this->userFinderByEmailService = new UserFinderByEmailService();
        $this->sender = new SendVerificationEmailService();
    }

    public function resend(ResendVerificationEmailRequest $request): void
    {
        $user = $this->userFinderByEmailService->find($request->getEmail());
       
        if ($user->getIsVerified()) {
            throw new AlreadyVerifiedException();
        }

        $this->sender->send($user);
    }
}