<?php

namespace App\Models\UserToken;

use App\Converter\UserToken\PrimitiveToUserTokenConverter;

use App\Entity\UserToken\UserToken;
use App\Tenancy\TenantContext;
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
                tenant_id, user_id, token, type, expires_at, created_at
            )
            VALUES (?, ?, ?, ?, ?, NOW())
        ";

        $this->db->query($query, [
            TenantContext::id(),
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
        $query = "SELECT * FROM user_verification_tokens WHERE token = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$token, TenantContext::id()]);
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
              AND tenant_id = ?
              AND used_at IS NULL
              AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ";
        $result = $this->db->query($query, [$userId, $type, TenantContext::id()]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function markAsUsed(int $id): void
    {
        $query = "UPDATE user_verification_tokens SET used_at = NOW() WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }

    public function deleteByUserAndType(int $userId, string $type): void
    {
        $query = "DELETE FROM user_verification_tokens WHERE user_id = ? AND type = ? AND tenant_id = ?";
        $this->db->query($query, [$userId, $type, TenantContext::id()]);
    }

    /**
     * Borra los tokens vencidos del Tenant del contexto. Un proceso de limpieza que cubra a todos
     * los Tenants debe recorrerlos y fijar el contexto de cada uno (como el de recordatorios).
     */
    public function deleteExpired(): int
    {
        $query = "DELETE FROM user_verification_tokens WHERE expires_at < NOW() AND tenant_id = ?";
        $this->db->query($query, [TenantContext::id()]);
        return $this->db->affectedRows();
    }
}
