<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Esquema de `platform_admins` y `platform_admin_tokens`: tablas globales (sin tenant_id).
 *
 * @internal
 */
final class PlatformSchemaTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private function insertAdmin(string $email = 'root@plataforma.test'): int
    {
        $this->db->query(
            "INSERT INTO platform_admins (email, first_name, last_name, password) VALUES (?,?,?,?)",
            [$email, 'Root', 'Admin', password_hash('x', PASSWORD_DEFAULT)]
        );

        return (int) $this->db->insertID();
    }

    public function testPlatformTablesHaveNoTenantId(): void
    {
        foreach (['platform_admins', 'platform_admin_tokens'] as $table) {
            $column = $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getRow();

            $this->assertNull($column, "{$table} no debe tener tenant_id (ADR 003)");
        }
    }

    public function testEmailIsUnique(): void
    {
        $this->insertAdmin();

        $this->expectException(DatabaseException::class);
        $this->insertAdmin();
    }

    public function testAdminIsActiveByDefault(): void
    {
        $id = $this->insertAdmin();

        $row = $this->db->query("SELECT is_active FROM platform_admins WHERE id = ?", [$id])->getRow();

        $this->assertSame('1', (string) $row->is_active);
    }

    public function testTokenIsUnique(): void
    {
        $id = $this->insertAdmin();
        $sql = "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?,?,?)";
        $this->db->query($sql, [$id, 'tok-1', '2099-01-01 00:00:00']);

        $this->expectException(DatabaseException::class);
        $this->db->query($sql, [$id, 'tok-1', '2099-01-01 00:00:00']);
    }

    public function testTokensAreDeletedWithTheirAdmin(): void
    {
        $id = $this->insertAdmin();
        $this->db->query(
            "INSERT INTO platform_admin_tokens (platform_admin_id, token, expires_at) VALUES (?,?,?)",
            [$id, 'tok-2', '2099-01-01 00:00:00']
        );

        $this->db->query("DELETE FROM platform_admins WHERE id = ?", [$id]);

        $count = $this->db->query("SELECT COUNT(*) AS c FROM platform_admin_tokens")->getRow()->c;
        $this->assertSame(0, (int) $count);
    }
}
