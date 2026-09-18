<?php

namespace App\Services\User;

use App\Email\User\UserEmailVerificationService;
use App\Entity\UserToken\UserToken;
use App\Exception\User\InvalidVerificationTokenException;
use App\Exception\User\UserAlreadyVerifiedException;
use App\Models\User\UserModel;
use App\Models\UserToken\UserTokenModel;
use App\Services\User\UserFinderService;
use CodeIgniter\Database\BaseConnection;
use DateTime;

final class UserVerificationAccountService
{
    private UserTokenModel $tokenModel;
    private UserFinderService $userFinderService;
    private UserModel $userModel;

    private UserEmailVerificationService $emailVerification;
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->tokenModel = new UserTokenModel();
        $this->userFinderService = new UserFinderService();
        $this->userModel = new UserModel();
        $this->emailVerification = new UserEmailVerificationService();
    }


    public function generateEmailVerificationToken(int $userId): string
    {
        $this->tokenModel->deleteByUserAndType($userId, 'email_verification');

        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);     

        $this->tokenModel->insert(new UserToken(
            null,
            $userId,
            $hash,
            'email_verification',
            new DateTime('+24 hours'),
            null,
            new DateTime()
        ));

        return $token;
    }
    
    public function verifyEmail(string $token): void
    {
        $verification = $this->tokenModel->findByToken(hash('sha256', $token));

        if (!$verification) {
            throw new InvalidVerificationTokenException();
        }

        if (!$verification->isValid()) {
            throw new InvalidVerificationTokenException('El token expiró o ya fue usado.');
        }

        $user = $this->userFinderService->find($verification->getUserId());

        if ($user->getIsVerified()) {
            throw new UserAlreadyVerifiedException();
        }

        $this->db->transBegin();
        try {
            $user->markAsVerified();
            $this->userModel->update($user);
            $this->tokenModel->markAsUsed($verification->getId());
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function sendVerificationEmail(int $userId, string $token): void
    {
        try {
            $user = $this->userFinderService->find($userId);
            $this->emailVerification->send($user, $token);
        } catch (\Throwable $e) {
            log_message('error', 'Error enviando email de verificación: ' . $e->getMessage());
        }
    }
}
