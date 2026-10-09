<?php

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Dto\Request\User\UserFilterRequest;
use App\Entity\Patient\Patient;
use App\Entity\Tenant\Tenant;
use App\Entity\User\User;
use App\Models\Patient\PatientModel;
use App\Models\User\UserModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Un Tenant nunca ve, busca, modifica ni borra los users/patients de otro.
 *
 * @internal
 */
final class UserPatientTenantIsolationTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantA = 1;
    private int $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        // El down() de la migración no puede restaurar los únicos globales si dos Tenants comparten
        // email o DNI: se limpia lo sembrado para que el rollback del siguiente refresh no falle.
        $this->db->query('DELETE FROM patients');
        $this->db->query('DELETE FROM users WHERE tenant_id <> 1');
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    /** Inserta un user (y su paciente) directamente en el Tenant indicado. */
    private function seedUserWithPatient(int $tenantId, string $email, int $dni): array
    {
        $this->db->table('users')->insert([
            'tenant_id'  => $tenantId,
            'first_name' => 'Nombre',
            'last_name'  => 'Apellido',
            'email'      => $email,
            'password'   => 'hash',
            'dni'        => $dni,
        ]);
        $userId = $this->db->insertID();

        $this->db->table('patients')->insert([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'birth_date' => '1990-01-01',
        ]);

        return [$userId, $this->db->insertID()];
    }

    public function testSameEmailAndDniCanExistInTwoTenants(): void
    {
        $this->seedUserWithPatient($this->tenantA, 'igual@test.com', 30111222);
        $this->seedUserWithPatient($this->tenantB, 'igual@test.com', 30111222);

        $this->assertSame(2, (int) $this->db->table('users')->where('email', 'igual@test.com')->countAllResults());
    }

    public function testSameEmailTwiceInTheSameTenantIsRejected(): void
    {
        $this->seedUserWithPatient($this->tenantA, 'dup@test.com', 30111001);

        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $this->seedUserWithPatient($this->tenantA, 'dup@test.com', 30111002);
    }

    public function testMigrationDownFailsCleanlyWhenTenantsShareEmailOrDni(): void
    {
        require_once APPPATH . 'Database/Migrations/2026-10-06-110000_AddTenantIdToUsersAndPatients.php';

        $this->seedUserWithPatient($this->tenantA, 'igual@test.com', 30111222);
        $this->seedUserWithPatient($this->tenantB, 'igual@test.com', 30111222);

        try {
            (new \App\Database\Migrations\AddTenantIdToUsersAndPatients())->down();
            $this->fail('Se esperaba RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No se puede revertir', $e->getMessage());
            $this->assertStringContainsString('emails/DNI duplicados entre tenants', $e->getMessage());
            $this->assertStringContainsString('igual@test.com', $e->getMessage());
            $this->assertStringContainsString('30111222', $e->getMessage());
        }

        // La base quedó intacta: el esquema por Tenant sigue completo.
        $this->assertNotEmpty($this->db->query("SHOW COLUMNS FROM users WHERE Field = 'tenant_id'")->getResultArray());
        $this->assertNotEmpty($this->db->query("SHOW COLUMNS FROM patients WHERE Field = 'tenant_id'")->getResultArray());
        $this->assertNotEmpty($this->db->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_tenant_email'")->getResultArray());
    }

    public function testUserModelDoesNotSeeOtherTenantUsers(): void
    {
        [$userA] = $this->seedUserWithPatient($this->tenantA, 'a@test.com', 30000001);
        [$userB] = $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);

        $this->actAs($this->tenantA);
        $model = new UserModel();

        $this->assertNotNull($model->find($userA));
        $this->assertNull($model->find($userB));
        $this->assertNull($model->findByEmail('b@test.com'));
        $this->assertNull($model->findByDni('30000002'));
    }

    public function testUserSearchAndCountAreScopedToTheTenant(): void
    {
        $this->seedUserWithPatient($this->tenantA, 'a@test.com', 30000001);
        $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);
        $this->seedUserWithPatient($this->tenantB, 'c@test.com', 30000003);

        $this->actAs($this->tenantB);
        $model = new UserModel();
        $filter = new UserFilterRequest(new PaginationRequest(1, 50), null, null, null, null);

        $this->assertSame(2, $model->countByFilter($filter));
        $this->assertCount(2, $model->search($filter));
    }

    public function testUserModelCannotDeleteOrUpdateOtherTenantUsers(): void
    {
        [$userB] = $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);

        $this->actAs($this->tenantA);
        $model = new UserModel();
        $model->delete($userB);
        $model->markAsVerified($userB);
        $model->updatePassword($userB, 'nuevo');

        $row = $this->db->table('users')->where('id', $userB)->get()->getRow();
        $this->assertNotNull($row);
        $this->assertSame('hash', $row->password);
        $this->assertSame(0, (int) $row->is_verified);
    }

    public function testInsertsUseTheContextTenant(): void
    {
        $this->actAs($this->tenantB);

        $user = (new UserModel())->insert(
            new User(null, 'X', 'Y', 'x@test.com', null, 'hash', 30000010, true, false, null, null, null, null, null)
        );
        $patient = (new PatientModel())->insert(
            new Patient(null, $user->getId(), null, new DateTime(), new DateTime())
        );

        $this->assertSame(
            $this->tenantB,
            (int) $this->db->table('users')->where('id', $user->getId())->get()->getRow()->tenant_id
        );
        $this->assertSame(
            $this->tenantB,
            (int) $this->db->table('patients')->where('id', $patient->getId())->get()->getRow()->tenant_id
        );

        $this->actAs($this->tenantA);
        $this->assertNull((new UserModel())->find($user->getId()));
    }

    public function testPatientModelDoesNotSeeOtherTenantPatients(): void
    {
        [$userA, $patientA] = $this->seedUserWithPatient($this->tenantA, 'a@test.com', 30000001);
        [$userB, $patientB] = $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);

        $this->actAs($this->tenantA);
        $model = new PatientModel();

        $this->assertNotNull($model->find($patientA));
        $this->assertNull($model->find($patientB));
        $this->assertNull($model->findByUserId($userB));
        $this->assertNull($model->findProjection($patientB));
        $this->assertNull($model->findByUserIdProjection($userB));
    }

    public function testPatientSearchAndCountAreScopedToTheTenant(): void
    {
        $this->seedUserWithPatient($this->tenantA, 'a@test.com', 30000001);
        $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);

        $this->actAs($this->tenantA);
        $model = new PatientModel();
        $filter = new PatientFilterRequest(new PaginationRequest(1, 50));

        $this->assertSame(1, $model->countProjection($filter));
        $this->assertCount(1, $model->searchProjection($filter));
        $this->assertCount(1, $model->search($filter));
    }

    public function testPatientModelCannotDeleteOtherTenantPatients(): void
    {
        [, $patientB] = $this->seedUserWithPatient($this->tenantB, 'b@test.com', 30000002);

        $this->actAs($this->tenantA);
        (new PatientModel())->delete($patientB);

        $this->assertSame(1, (int) $this->db->table('patients')->where('id', $patientB)->countAllResults());
    }
}
