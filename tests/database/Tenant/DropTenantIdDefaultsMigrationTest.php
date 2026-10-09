<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

require_once APPPATH . 'Database/Migrations/2026-10-07-100000_DropTenantIdDefaults.php';

/**
 * @internal
 */
final class DropTenantIdDefaultsMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private const TABLES = [
        'users', 'patients', 'specialties', 'services', 'medicals',
        'medical_schedules', 'medical_service', 'appointments', 'user_verification_tokens',
    ];

    /** Estos tests corren down()/up() a mano: si algo falla a mitad de camino se restaura el estado final. */
    protected function tearDown(): void
    {
        $this->migration()->up();

        parent::tearDown();
    }

    private function migration(): \App\Database\Migrations\DropTenantIdDefaults
    {
        return new \App\Database\Migrations\DropTenantIdDefaults();
    }

    private function tenantIdColumn(string $table): object
    {
        return $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getRow();
    }

    /** @return array<string, array{string}> */
    public static function tablesProvider(): array
    {
        return array_combine(self::TABLES, array_map(static fn (string $t) => [$t], self::TABLES));
    }

    public function testNoTableKeepsADefaultForTenantId(): void
    {
        foreach (self::TABLES as $table) {
            $column = $this->tenantIdColumn($table);

            $this->assertNotNull($column, "{$table} debe tener tenant_id");
            $this->assertSame('NO', $column->Null, "{$table}.tenant_id sigue siendo NOT NULL");
            $this->assertNull($column->Default, "{$table}.tenant_id no debe tener DEFAULT");
        }
    }

    #[DataProvider('tablesProvider')]
    public function testRawInsertWithoutTenantIdFails(string $table): void
    {
        try {
            // Solo se omite tenant_id: el resto lo rechazaría otra columna, pero tenant_id es la
            // primera después de id y MySQL reporta esa. Si el modo SQL no fuera estricto, el 0
            // implícito lo rechaza la FK a tenants, que también nombra tenant_id.
            $this->db->query("INSERT INTO {$table} () VALUES ()");
            $this->fail("El INSERT sin tenant_id en {$table} no debió funcionar");
        } catch (DatabaseException $e) {
            $this->assertStringContainsString('tenant_id', $e->getMessage(), "{$table}: el error debe nombrar tenant_id");
        }
    }

    public function testDownRestoresTheTransitoryDefaultAndCanBeRetried(): void
    {
        $migration = $this->migration();

        $migration->down();
        $migration->down(); // reintento: no falla aunque ya esté restaurado

        foreach (self::TABLES as $table) {
            $this->assertSame('1', (string) $this->tenantIdColumn($table)->Default, "{$table} vuelve al DEFAULT 1");
        }

        $migration->up();
        $migration->up(); // idempotente

        foreach (self::TABLES as $table) {
            $this->assertNull($this->tenantIdColumn($table)->Default, "{$table} vuelve a no tener DEFAULT");
        }
    }
}
