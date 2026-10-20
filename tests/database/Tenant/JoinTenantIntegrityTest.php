<?php

use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Entity\Tenant\Tenant;
use App\Models\Medical\MedicalModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Las claves foráneas solo comparan ids: nada impide que una fila del Tenant A apunte a un
 * user/specialty/medical/service del Tenant B (dato corrupto u hostil). Cada JOIN lleva
 * `AND X.tenant_id = A.tenant_id`, así que una referencia cruzada no puede filtrar datos del
 * otro Tenant en las proyecciones. Cada test siembra una fila "sana" de control y una con
 * referencia cruzada, y exige que solo la sana aparezca.
 *
 * @internal
 */
final class JoinTenantIntegrityTest extends CIUnitTestCase
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

        $this->actAs($this->tenantA);
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        foreach (['medical_service', 'medical_schedules', 'medicals', 'patients', 'services', 'specialties', 'users'] as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    private function page(): PaginationRequest
    {
        return new PaginationRequest(1, 50);
    }

    private function seedUser(int $tenantId): int
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'U', 'last_name' => uniqid(),
            'email' => uniqid() . '@test.com', 'password' => 'hash', 'dni' => random_int(10000000, 99999999),
        ]);

        return $this->db->insertID();
    }

    private function seedSpecialty(int $tenantId): int
    {
        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => 'Esp ' . uniqid()]);

        return $this->db->insertID();
    }

    private function seedService(int $tenantId): int
    {
        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => 'Srv ' . uniqid(), 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => 10,
        ]);

        return $this->db->insertID();
    }

    private function seedMedical(int $tenantId, int $userId, int $specialtyId): int
    {
        $this->db->table('medicals')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'tuition' => 'MAT-' . uniqid(), 'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function seedSchedule(int $tenantId, int $medicalId): int
    {
        $this->db->table('medical_schedules')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'day_of_week' => 1,
            'start_time' => '08:00:00', 'end_time' => '12:00:00',
        ]);

        return $this->db->insertID();
    }


    public function testMedicalProjectionDoesNotLeakAUserOrSpecialtyOfAnotherTenant(): void
    {
        $healthy = $this->seedMedical($this->tenantA, $this->seedUser($this->tenantA), $this->seedSpecialty($this->tenantA));
        $foreignUser = $this->seedUser($this->tenantB);
        $withForeignUser = $this->seedMedical($this->tenantA, $foreignUser, $this->seedSpecialty($this->tenantA));
        $withForeignSpecialty = $this->seedMedical($this->tenantA, $this->seedUser($this->tenantA), $this->seedSpecialty($this->tenantB));

        $model = new MedicalModel();
        $filter = new MedicalFilterRequest($this->page());

        $this->assertNotNull($model->findProjection($healthy));
        $this->assertNull($model->findProjection($withForeignUser));
        $this->assertNull($model->findProjection($withForeignSpecialty));
        $this->assertNull($model->findByUserIdProjection($foreignUser));

        $this->assertSame(1, $model->countProjection($filter));
        $this->assertCount(1, $model->searchProjection($filter));
        $this->assertSame(1, $model->countAvailableProjection($filter));
        $this->assertCount(1, $model->searchAvailableProjection($filter));
    }

    public function testScheduleProjectionDoesNotLeakAMedicalOrUserOfAnotherTenant(): void
    {
        $medicalA = $this->seedMedical($this->tenantA, $this->seedUser($this->tenantA), $this->seedSpecialty($this->tenantA));
        $healthy = $this->seedSchedule($this->tenantA, $medicalA);

        $medicalB = $this->seedMedical($this->tenantB, $this->seedUser($this->tenantB), $this->seedSpecialty($this->tenantB));
        $toForeignMedical = $this->seedSchedule($this->tenantA, $medicalB);

        // Horario y médico del Tenant A, pero el médico apunta a un user del Tenant B.
        $medicalWithForeignUser = $this->seedMedical($this->tenantA, $this->seedUser($this->tenantB), $this->seedSpecialty($this->tenantA));
        $viaForeignUser = $this->seedSchedule($this->tenantA, $medicalWithForeignUser);

        $model = new MedicalScheduleModel();
        $filter = new MedicalScheduleFilterRequest($this->page());

        $this->assertNotNull($model->findProjection($healthy));
        $this->assertNull($model->findProjection($toForeignMedical));
        $this->assertNull($model->findProjection($viaForeignUser));
        $this->assertCount(0, $model->findByMedicalIdProjection($medicalB));
        $this->assertCount(0, $model->findByMedicalIdProjection($medicalWithForeignUser));
        $this->assertCount(1, $model->findByMedicalIdProjection($medicalA));

        $this->assertSame([$healthy], array_map(static fn ($s) => $s->getId(), $model->searchProjection($filter)));
    }

    public function testMedicalServiceDoesNotLinkAServiceOfAnotherTenant(): void
    {
        $medical = $this->seedMedical($this->tenantA, $this->seedUser($this->tenantA), $this->seedSpecialty($this->tenantA));
        $ownService = $this->seedService($this->tenantA);
        $foreignService = $this->seedService($this->tenantB);

        foreach ([$ownService, $foreignService] as $serviceId) {
            $this->db->table('medical_service')->insert([
                'tenant_id' => $this->tenantA, 'medical_id' => $medical, 'service_id' => $serviceId,
            ]);
        }

        $model = new MedicalServiceModel();

        $this->assertNotNull($model->findActiveByMedicalAndService($medical, $ownService));
        $this->assertNull($model->findActiveByMedicalAndService($medical, $foreignService));

        $projection = $model->findServicesByMedicalIdProjection($medical);
        $this->assertCount(1, $projection);
        $this->assertSame($ownService, $projection[0]->getServiceId());
    }
}
