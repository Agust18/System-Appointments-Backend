<?php

use App\Constants\Role;
use App\Database\Seeds\RolesSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Database;

/**
 * El RolesSeeder deja los ids de App\Constants\Role, se puede repetir y nunca mueve el role_id
 * de los usuarios (solo renombra roles.name).
 *
 * @internal
 */
final class RolesSeederTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private function roles(): array
    {
        $rows = $this->db->query('SELECT id, name FROM roles ORDER BY id')->getResult();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = $row->name;
        }

        return $map;
    }

    private function runSeeder(): void
    {
        ob_start();
        try {
            (new RolesSeeder(config(Database::class), $this->db))->run();
        } finally {
            ob_end_clean();
        }
    }

    public function testSeedsTheFourRolesWithConstantIds(): void
    {
        $this->runSeeder();

        $this->assertSame([
            Role::ADMIN => 'admin',
            Role::MEDICAL => 'medical',
            Role::PATIENT => 'patient',
            Role::RECEPTION => 'reception',
        ], $this->roles());
    }

    public function testIsIdempotent(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertCount(4, $this->roles());
    }

    public function testFixesSwappedNamesWithoutMovingUserRoleIds(): void
    {
        $this->db->table('roles')->insertBatch([
            ['id' => Role::ADMIN, 'name' => 'admin'],
            ['id' => 2, 'name' => 'patient'],
            ['id' => 3, 'name' => 'medical'],
        ]);
        $this->db->table('users')->insert([
            'tenant_id' => 1,
            'first_name' => 'Usuario',
            'last_name' => 'Apellido',
            'email' => uniqid() . '@test.com',
            'password' => 'hash',
            'dni' => random_int(1000000, 9999999),
            'role_id' => 2,
        ]);
        $userId = $this->db->insertID();

        $this->runSeeder();

        $this->assertSame('medical', $this->roles()[2]);
        $this->assertSame('patient', $this->roles()[3]);
        $this->assertSame(2, (int) $this->db->query('SELECT role_id FROM users WHERE id = ?', [$userId])->getRow()->role_id);
    }
}
