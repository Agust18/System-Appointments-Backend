<?php

use App\Database\Migrations\MakePatientsBirthDateNullableAndUniqueUserId;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

require_once APPPATH . 'Database/Migrations/2026-10-02-120000_MakePatientsBirthDateNullableAndUniqueUserId.php';

/**
 * Esquema de las migraciones MakeUsersPasswordNullable y
 * MakePatientsBirthDateNullableAndUniqueUserId.
 *
 * @internal
 */
final class PatientSchemaMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    /**
     * El test de pre-check deja la base sin el índice y con pacientes duplicados;
     * el refresh del siguiente test necesita el índice para revertir la migración.
     */
    protected function tearDown(): void
    {
        if (!$this->userIdIndexIsUnique()) {
            $this->db->query('DELETE FROM patients');
            $this->db->query('ALTER TABLE patients ADD UNIQUE KEY uq_patients_user_id (user_id)');
        }

        parent::tearDown();
    }

    private function insertUser(): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'usuario_' . uniqid(),
            'last_name'  => 'Prueba',
            'email'      => uniqid() . '@test.com',
            'password'   => null,
            'dni'        => random_int(1000000, 9999999),
        ]);

        return (int) $this->db->insertID();
    }

    private function userIdIndexIsUnique(): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM patients WHERE Key_name = 'uq_patients_user_id'")->getResultArray();

        return $rows !== [] && (int) $rows[0]['Non_unique'] === 0;
    }

    public function testUsersPasswordIsNullable(): void
    {
        $column = $this->db->query("SHOW COLUMNS FROM users WHERE Field = 'password'")->getRowArray();

        $this->assertSame('YES', $column['Null']);
        $this->seeInDatabase('users', ['id' => $this->insertUser(), 'password' => null]);
    }

    public function testPatientsBirthDateIsNullable(): void
    {
        $userId = $this->insertUser();
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => null]);

        $this->seeInDatabase('patients', ['user_id' => $userId, 'birth_date' => null]);
    }

    public function testPatientsUserIdIsUnique(): void
    {
        $this->assertTrue($this->userIdIndexIsUnique());

        $userId = $this->insertUser();
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId]);

        $this->expectException(DatabaseException::class);
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId]);
    }

    public function testUpAbortsListingUsersWithMoreThanOnePatientAndChangesNothing(): void
    {
        $this->db->query('ALTER TABLE patients DROP INDEX uq_patients_user_id');

        $userId = $this->insertUser();
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => '1990-01-01']);
        $first = (int) $this->db->insertID();
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => '1991-01-01']);
        $second = (int) $this->db->insertID();

        try {
            (new MakePatientsBirthDateNullableAndUniqueUserId())->up();
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('más de un paciente', $e->getMessage());
            $this->assertStringContainsString("users.id {$userId}", $e->getMessage());
            $this->assertStringContainsString("{$first}, {$second}", $e->getMessage());
        }

        $this->assertFalse($this->userIdIndexIsUnique());
        $this->seeNumRecords(2, 'patients', ['user_id' => $userId]);
    }
}
