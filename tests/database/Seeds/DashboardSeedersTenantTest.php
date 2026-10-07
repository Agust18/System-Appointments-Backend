<?php

use App\Constants\Role;
use App\Database\Seeds\DashboardDemoCleanupSeeder;
use App\Database\Seeds\DashboardDemoSeeder;
use App\Entity\Tenant\Tenant;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Database;

/**
 * Los seeders del dashboard no dependen del DEFAULT de tenant_id: exigen `seed.tenantSlug`
 * (abortan sin escribir si falta o no existe), escriben tenant_id explícito y la limpieza solo
 * borra en ese Tenant.
 *
 * Los seeders están excluidos de git (.git/info/exclude): en una máquina que no los tenga estos
 * tests se saltean.
 *
 * @internal
 */
final class DashboardSeedersTenantTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantB;

    protected function setUp(): void
    {
        if (!class_exists(DashboardDemoSeeder::class) || !class_exists(DashboardDemoCleanupSeeder::class)) {
            $this->markTestSkipped('Los seeders del dashboard son locales (excluidos de git) y no existen en esta máquina.');
        }

        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        // Los usuarios del seeder llevan role_id MEDICAL (2) y PATIENT (3): la FK exige que existan.
        // Sin ellos el INSERT falla en silencio dentro de la transacción (CI4 no lanza ahí).
        $this->db->table('roles')->insertBatch([
            ['id' => Role::MEDICAL, 'name' => 'medical'],
            ['id' => Role::PATIENT, 'name' => 'patient'],
        ]);

        $this->setSeedSlug(null);
        putenv('DRY_RUN');
    }

    protected function tearDown(): void
    {
        $this->setSeedSlug(null);
        putenv('DRY_RUN');
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));

        // Deja la base sin datos de seed para el siguiente refresh.
        foreach (['appointments', 'medical_service', 'medicals', 'patients', 'services', 'specialties', 'users', 'roles'] as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function setSeedSlug(?string $slug): void
    {
        if ($slug === null) {
            unset($_ENV['seed.tenantSlug'], $_SERVER['seed.tenantSlug']);
            putenv('seed.tenantSlug'); // env() también lee getenv(): sin esto el .env local se filtra al test

            return;
        }

        $_ENV['seed.tenantSlug'] = $slug;
        $_SERVER['seed.tenantSlug'] = $slug;
    }

    /** Corre un seeder capturando lo que imprime (la suite falla si un test imprime). */
    private function runSeeder(string $seederClass): string
    {
        ob_start();
        try {
            (new $seederClass(config(Database::class), $this->db))->run();
        } finally {
            $output = ob_get_clean();
        }

        return $output;
    }

    private function countIn(string $table, int $tenantId): int
    {
        return (int) $this->db->table($table)->where('tenant_id', $tenantId)->countAllResults();
    }

    private function totalRows(): int
    {
        $total = 0;
        foreach (['users', 'patients', 'medicals', 'services', 'specialties', 'medical_service', 'appointments'] as $table) {
            $total += (int) $this->db->table($table)->countAllResults();
        }

        return $total;
    }

    public function testSeederAbortsWithoutWritingWhenTheSlugIsMissing(): void
    {
        try {
            $this->runSeeder(DashboardDemoSeeder::class);
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('seed.tenantSlug', $e->getMessage());
        }

        $this->assertSame(0, $this->totalRows(), 'no debe caer al DEFAULT 1 ni sembrar nada');
    }

    public function testSeederAbortsWithoutWritingWhenTheTenantDoesNotExist(): void
    {
        $this->setSeedSlug('no-existe');

        try {
            $this->runSeeder(DashboardDemoSeeder::class);
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no existe', $e->getMessage());
        }

        $this->assertSame(0, $this->totalRows());
    }

    public function testCleanupAlsoAbortsWithoutASlugAndDeletesNothing(): void
    {
        $this->setSeedSlug('tenant-b');
        $this->runSeeder(DashboardDemoSeeder::class);
        $rows = $this->totalRows();
        $this->assertGreaterThan(0, $rows);

        $this->setSeedSlug(null);
        try {
            $this->runSeeder(DashboardDemoCleanupSeeder::class);
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException) {
        }

        $this->assertSame($rows, $this->totalRows(), 'sin Tenant explícito la limpieza no borra nada');
    }

    public function testSeederWritesEveryRowInTheChosenTenant(): void
    {
        $this->setSeedSlug('tenant-b');

        $this->runSeeder(DashboardDemoSeeder::class);

        $expected = ['users' => 11, 'specialties' => 4, 'services' => 4, 'medicals' => 4, 'patients' => 7];
        foreach ($expected as $table => $count) {
            $this->assertSame($count, $this->countIn($table, $this->tenantB), "{$table} en el Tenant elegido");
            $this->assertSame(0, $this->countIn($table, 1), "{$table} no debe quedar en el Tenant 1 por el DEFAULT");
        }

        $this->assertGreaterThan(0, $this->countIn('medical_service', $this->tenantB));
        $this->assertGreaterThan(0, $this->countIn('appointments', $this->tenantB));
        $this->assertSame(0, $this->countIn('medical_service', 1));
        $this->assertSame(0, $this->countIn('appointments', 1));
    }

    public function testSeederRestoresThePreviousTenantContext(): void
    {
        $before = new Tenant(1, 'demo', 'Demo', 'active', null, null);
        TenantContext::set($before);
        $this->setSeedSlug('tenant-b');

        $this->runSeeder(DashboardDemoSeeder::class);

        $this->assertSame($before, TenantContext::current());
    }

    public function testDryRunWritesNothing(): void
    {
        $this->setSeedSlug('tenant-b');
        putenv('DRY_RUN=1');

        $output = $this->runSeeder(DashboardDemoSeeder::class);

        $this->assertStringContainsString('tenant-b', $output);
        $this->assertSame(0, $this->totalRows());
    }

    public function testTwoTenantsCanBeSeededAndCleanupOnlyDeletesTheChosenOne(): void
    {
        $this->setSeedSlug('demo');
        $this->runSeeder(DashboardDemoSeeder::class);
        $this->setSeedSlug('tenant-b');
        $this->runSeeder(DashboardDemoSeeder::class);

        // Mismos emails, DNI y nombres en dos Tenants: los únicos son por Tenant.
        $this->assertSame(11, $this->countIn('users', 1));
        $this->assertSame(11, $this->countIn('users', $this->tenantB));

        $this->runSeeder(DashboardDemoCleanupSeeder::class);

        foreach (['users', 'specialties', 'services', 'medicals', 'patients', 'medical_service', 'appointments'] as $table) {
            $this->assertSame(0, $this->countIn($table, $this->tenantB), "{$table} del Tenant limpiado");
        }
        $this->assertSame(11, $this->countIn('users', 1), 'los datos de seed del otro Tenant siguen intactos');
        $this->assertSame(4, $this->countIn('services', 1));
        $this->assertSame(4, $this->countIn('specialties', 1));
    }
}
