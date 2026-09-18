<?php

namespace App\Models\UserToken;

use App\Converter\UserToken\PrimitiveToUserTokenConverter;

use App\Entity\UserToken\UserToken;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

final class UserTokenModel
{
    private BaseConnection $db;
    private PrimitiveToUserTokenConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToUserTokenConverter();
    }

    public function insert(UserToken $token): UserToken
    {
        $query = "
            INSERT INTO user_verification_tokens (
                user_id, token, type, expires_at, created_at
            )
            VALUES (?, ?, ?, ?, NOW())
        ";

        $this->db->query($query, [
            $token->getUserId(),
            $token->getToken(),
            $token->getType(),
            $token->getExpiresAt()->format('Y-m-d H:i:s'),
        ]);

        $id = $this->db->insertID();

        return new UserToken(
            $id,
            $token->getUserId(),
            $token->getToken(),
            $token->getType(),
            $token->getExpiresAt(),
            null,
            new DateTime()
        );
    }

    public function findByToken(string $token): ?UserToken
    {
        $query = "SELECT * FROM user_verification_tokens WHERE token = ?";
        $result = $this->db->query($query, [$token]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function findActiveByUserAndType(int $userId, string $type): ?UserToken
    {
        $query = "
            SELECT * FROM user_verification_tokens 
            WHERE user_id = ? 
              AND type = ? 
              AND used_at IS NULL 
              AND expires_at > NOW()
            ORDER BY id DESC 
            LIMIT 1
        ";
        $result = $this->db->query($query, [$userId, $type]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function markAsUsed(int $id): void
    {
        $query = "UPDATE user_verification_tokens SET used_at = NOW() WHERE id = ?";
        $this->db->query($query, [$id]);
    }

    public function deleteByUserAndType(int $userId, string $type): void
    {
        $query = "DELETE FROM user_verification_tokens WHERE user_id = ? AND type = ?";
        $this->db->query($query, [$userId, $type]);
    }

    public function deleteExpired(): int
    {
        $query = "DELETE FROM user_verification_tokens WHERE expires_at < NOW()";
        $this->db->query($query);
        return $this->db->affectedRows();
    }
}
