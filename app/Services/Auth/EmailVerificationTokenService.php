<?php

namespace App\Services\auth;

use App\Entity\UserToken\UserToken;
use App\Exception\Auth\InvalidVerificationTokenException;
use App\Models\UserToken\UserTokenModel;
use DateTime;

final class EmailVerificationTokenService
{
    private const TYPE = 'email_verification';
    private const TTL = '+24 hours';

    private UserTokenModel $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new UserTokenModel();
    }

    public function generateForUser(int $userId): UserToken
    {
        $this->tokenModel->deleteByUserAndType($userId, self::TYPE);

        $token = new UserToken(
            null,
            $userId,
            bin2hex(random_bytes(32)),
            self::TYPE,
            new DateTime(self::TTL),
            null,
            new DateTime(),
        );

        return $this->tokenModel->insert($token);
    }

    public function validate(string $value): UserToken
    {   
         
        $token = $this->tokenModel->findByToken($value);

        if (!$token || $token->getType() !== self::TYPE || !$token->isValid()) {
            throw new InvalidVerificationTokenException();
        }
        
        return $token;
    }

    public function markAsUsed(UserToken $token): void
    {
        $id = $token->getId();
        if ($id !== null) {
            $this->tokenModel->markAsUsed($id);
        }
    }
}