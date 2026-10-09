<?php

use App\Database\Migrations\NormalizeUserIdentityAndUniqueDni;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

require_once APPPATH . 'Database/Migrations/2026-10-02-100000_NormalizeUserIdentityAndUniqueDni.php';

/**
 * @internal
 */
final class NormalizeUserIdentityMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    /**
     * Con el multitenant (AddTenantIdToUsersAndPatients) el único de DNI del esquema final es
     * `uq_users_tenant_dni (tenant_id, dni)`; el global `uq_users_dni` solo lo crea el up() de
     * esta migración cuando se la corre a mano en estos tests.
     *
     * Los tests que simulan un `up()` abortado dejan la base sin el índice por Tenant y con DNIs
     * duplicados; el refresh del siguiente test necesita el esquema final para poder revertir
     * las migraciones posteriores, así que se lo restaura.
     */
    protected function tearDown(): void
    {
        if ($this->indexExists('uq_users_dni')) {
            $this->db->query('ALTER TABLE users DROP INDEX uq_users_dni');
        }

        if (!$this->indexExists('uq_users_tenant_dni')) {
            $this->db->query('DELETE FROM users');
            $this->db->query('ALTER TABLE users ADD UNIQUE KEY uq_users_tenant_dni (tenant_id, dni)');
        }

        parent::tearDown();
    }

    private function indexExists(string $name): bool
    {
        return $this->db->query('SHOW INDEX FROM users WHERE Key_name = ?', [$name])->getResultArray() !== [];
    }

    private function insertUser(string $email, int $dni, ?string $phone = null): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            // Nombre único: el down() de ReplaceUsernameWithFirstLastName reconstruye
            // users.username (UNIQUE) desde nombre + apellido al refrescar la base.
            'first_name' => 'usuario_' . uniqid(),
            'last_name'  => 'Prueba',
            'email'      => $email,
            'phone'      => $phone,
            'password'   => 'hash',
            'dni'        => $dni,
        ]);

        return (int) $this->db->insertID();
    }

    /** Simula el estado previo a la migración: sin ningún único sobre el DNI. */
    private function dropDniIndex(): void
    {
        $this->db->query('ALTER TABLE users DROP INDEX uq_users_tenant_dni');
    }

    private function tenantDniIndexIsUnique(): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_tenant_dni'")->getResultArray();

        return $rows !== [] && (int) $rows[0]['Non_unique'] === 0;
    }

    private function dniIndexIsUnique(): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_dni'")->getResultArray();

        return $rows !== [] && (int) $rows[0]['Non_unique'] === 0;
    }

    public function testMigratedSchemaEnforcesUniqueDni(): void
    {
        $this->assertTrue($this->tenantDniIndexIsUnique());

        $this->insertUser('a@test.com', 30123456);
        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $this->insertUser('b@test.com', 30123456);
    }

    public function testUpNormalizesEmailAndPhoneAndCreatesTheIndex(): void
    {
        $this->dropDniIndex();

        $messy = $this->insertUser('  Juan.Perez@Mail.COM ', 30123456, '2325-506499');
        $blank = $this->insertUser('otro@test.com', 30123457, '  ');
        $clean = $this->insertUser('limpio@test.com', 30123458, '2325506499');

        (new NormalizeUserIdentityAndUniqueDni())->up();

        $this->seeInDatabase('users', ['id' => $messy, 'email' => 'juan.perez@mail.com', 'phone' => '2325506499']);
        $this->seeInDatabase('users', ['id' => $blank, 'phone' => null]);
        $this->seeInDatabase('users', ['id' => $clean, 'email' => 'limpio@test.com', 'phone' => '2325506499']);
        $this->assertTrue($this->dniIndexIsUnique());
    }

    public function testUpAbortsListingDuplicateDnisAndChangesNothing(): void
    {
        $this->dropDniIndex();

        $first  = $this->insertUser(' Primero@Test.com', 30123456, '2325-506499');
        $second = $this->insertUser('segundo@test.com', 30123456);

        try {
            (new NormalizeUserIdentityAndUniqueDni())->up();
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('DNIs duplicados', $e->getMessage());
            $this->assertStringContainsString('30123456', $e->getMessage());
            $this->assertStringContainsString("{$first}, {$second}", $e->getMessage());
        }

        $this->seeInDatabase('users', ['id' => $first, 'email' => ' Primero@Test.com', 'phone' => '2325-506499']);
        $this->assertFalse($this->dniIndexIsUnique());
    }

    public function testUpAbortsWhenNormalizedEmailsCollide(): void
    {
        $this->dropDniIndex();

        $this->insertUser('Mismo@Test.com', 30123456);
        $this->insertUser('mismo@test.com ', 30123457);

        try {
            (new NormalizeUserIdentityAndUniqueDni())->up();
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('emails que colisionan', $e->getMessage());
            $this->assertStringContainsString('mismo@test.com', $e->getMessage());
        }

        $this->seeInDatabase('users', ['email' => 'Mismo@Test.com']);
        $this->assertFalse($this->dniIndexIsUnique());
    }
}
