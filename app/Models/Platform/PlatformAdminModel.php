<?php

namespace App\Models\Platform;

use App\Converter\Platform\PrimitiveToPlatformAdminConverter;
use App\Entity\Platform\PlatformAdmin;
use App\Entity\Platform\PlatformAdminToken;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

/**
 * Las tablas `platform_admins` y `platform_admin_tokens` son globales: no llevan `tenant_id`
 * ni usan TenantContext (en /platform/* ese contexto está vacío a propósito).
 */
final class PlatformAdminModel
{
    private BaseConnection $db;
    private PrimitiveToPlatformAdminConverter $converter;

    private const COLUMNS = "A.id, A.email, A.first_name, A.last_name, A.password, A.is_active, A.created_at, A.updated_at";

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToPlatformAdminConverter();
    }

    public function insert(PlatformAdmin $admin): PlatformAdmin
    {
        $query = "INSERT INTO platform_admins (email, first_name, last_name, password, is_active) VALUES (?,?,?,?,?)";

        $this->db->query($query, [
            $admin->getEmail(),
            $admin->getFirstName(),
            $admin->getLastName(),
            $admin->getPassword(),
            $admin->isActive() ? 1 : 0,
        ]);

        return $this->find($this->db->insertID());
    }

    public function find(int $id): ?PlatformAdmin
    {
        $result = $this->db->query("SELECT " . self::COLUMNS . " FROM platform_admins A WHERE A.id = ?", [$id]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }

    public function findByEmail(string $email): ?PlatformAdmin
    {
        $result = $this->db->query("SELECT " . self::COLUMNS . " FROM platform_admins A WHERE A.email = ?", [$email]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }

    /**
     * El platform_admin dueño de un token vigente. No mira `is_active`: eso lo decide el filtro,
     * que responde 403 (y no 401) a un admin desactivado.
     */
    public function findByToken(string $token): ?PlatformAdmin
    {
        $query = "SELECT " . self::COLUMNS . " FROM platform_admins A ";
        $query .= "INNER JOIN platform_admin_tokens T ON T.platform_admin_id = A.id ";
        $query .= "WHERE T.token = ? AND T.expires_at >= ?";

        $result = $this->db->query($query, [$token, (new DateTime())->format('Y-m-d H:i:s')]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }

    public function saveToken(PlatformAdminToken $token): void
    {
        $this->db->query(
            "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?,?,?)",
            [
                $token->getPlatformAdminId(),
                $token->getToken(),
                $token->getExpiresAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function deleteExpiredTokens(int $platformAdminId): void
    {
        $this->db->query(
            "DELETE FROM platform_admin_tokens WHERE platform_admin_id = ? AND expires_at < ?",
            [$platformAdminId, (new DateTime())->format('Y-m-d H:i:s')]
        );
    }
}
