<?php

namespace App\Services\auth;

use App\Entity\UserToken\UserToken;
use App\Models\UserToken\UserTokenModel;
use DateTime;

abstract class AbstractUserTokenService
{
    protected UserTokenModel $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new UserTokenModel();
    }

    abstract protected function getType(): string;
    abstract protected function getTtl(): string;
    abstract protected function getInvalidTokenException(): \Throwable;

    public function generateForUser(int $userId): UserToken
    {
        $this->tokenModel->deleteByUserAndType($userId, $this->getType());

        $token = new UserToken(
            id: null,
            userId: $userId,
            token: $this->generateSecureToken(),
            type: $this->getType(),
            expiresAt: new DateTime($this->getTtl()),
            usedAt: null,
            createdAt: new DateTime()
        );

        return $this->tokenModel->insert($token);
    }

    public function validate(string $value): UserToken
    {
        $token = $this->tokenModel->findByToken($value);

        if (!$token || $token->getType() !== $this->getType() || !$token->isValid()) {
            throw $this->getInvalidTokenException();
        }

        return $token;
    }

    public function markAsUsed(UserToken $token): void
    {
        if ($token->getId() !== null) {
            $this->tokenModel->markAsUsed($token->getId());
        }
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}