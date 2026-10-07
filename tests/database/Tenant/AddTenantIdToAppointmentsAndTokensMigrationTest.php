<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

require_once APPPATH . 'Database/Migrations/2026-10-06-130000_AddTenantIdToAppointmentsAndTokens.php';

/**
 * @internal
 */
final class AddTenantIdToAppointmentsAndTokensMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    /**
     * Estos tests corren el down() a mano: si algo falla a mitad de camino la base queda sin las
     * columnas y el rollback del siguiente refresh no podría revertir lo que no existe. Se la
     * restaura siempre.
     */
    protected function tearDown(): void
    {
        if (!$this->columnExists('appointments', 'tenant_id') || !$this->columnExists('user_verification_tokens', 'tenant_id')) {
            $this->migration()->down();
            $this->migration()->up();
        }

        parent::tearDown();
    }

    private function migration(): \App\Database\Migrations\AddTenantIdToAppointmentsAndTokens
    {
        return new \App\Database\Migrations\AddTenantIdToAppointmentsAndTokens();
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])->getResultArray() !== [];
    }

    private function indexExists(string $table, string $index): bool
    {
        return $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])->getResultArray() !== [];
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint]
        )->getResultArray() !== [];
    }

    public function testUpAddsTenantIdWithoutDefaultAndWithForeignKeys(): void
    {
        foreach (['appointments', 'user_verification_tokens'] as $table) {
            $column = $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getRow();

            $this->assertNotNull($column, "{$table} debe tener tenant_id");
            $this->assertSame('NO', $column->Null, "{$table}.tenant_id es NOT NULL");
            $this->assertNull($column->Default, "{$table}.tenant_id ya no lleva DEFAULT (lo quita DropTenantIdDefaults)");
        }

        $this->assertTrue($this->foreignKeyExists('appointments', 'fk_appointments_tenant'));
        $this->assertTrue($this->foreignKeyExists('user_verification_tokens', 'fk_user_verification_tokens_tenant'));
        $this->assertTrue($this->indexExists('appointments', 'idx_appointments_tenant_fecha'));
    }

    public function testTokenKeepsItsGlobalUniqueIndex(): void
    {
        $unique = $this->db->query("SHOW INDEX FROM user_verification_tokens WHERE Key_name = 'token'")->getResultArray();

        $this->assertNotEmpty($unique);
        $this->assertSame(0, (int) $unique[0]['Non_unique']);
    }

    public function testTenantIdMustReferenceAnExistingTenant(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->query(
            "INSERT INTO user_verification_tokens (tenant_id, user_id, token, type, expires_at) VALUES (999, 1, 'x', 'email_verification', NOW())"
        );
    }

    public function testDownRemovesEverythingAndCanBeRetried(): void
    {
        $migration = $this->migration();

        $migration->down();
        $migration->down(); // reintento: no falla aunque ya no quede nada

        foreach (['appointments', 'user_verification_tokens'] as $table) {
            $this->assertFalse($this->columnExists($table, 'tenant_id'), "{$table} ya no debe tener tenant_id");
        }
        $this->assertFalse($this->foreignKeyExists('appointments', 'fk_appointments_tenant'));
        $this->assertFalse($this->indexExists('appointments', 'idx_appointments_tenant_fecha'));

        $migration->up(); // se restaura para el resto de la suite
        $this->assertTrue($this->columnExists('appointments', 'tenant_id'));
    }
}
