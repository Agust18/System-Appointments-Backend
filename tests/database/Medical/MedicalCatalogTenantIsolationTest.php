<?php

use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Dto\Request\Specialty\SpecialtyFilterRequest;
use App\Entity\Medical\Medical;
use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Entity\MedicalService\MedicalService;
use App\Entity\Service\Service;
use App\Entity\Specialty\Specialty;
use App\Entity\Tenant\Tenant;
use App\Models\Medical\MedicalModel;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Models\Specialty\SpecialtyModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Un Tenant nunca ve, busca, modifica ni borra especialidades, servicios, médicos, horarios
 * ni asignaciones médico-servicio de otro.
 *
 * @internal
 */
final class MedicalCatalogTenantIsolationTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantA = 1;
    private int $tenantB;

    /** @var array<string, int> ids sembrados en cada Tenant */
    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        $this->a = $this->seedWorld($this->tenantA, 'a');
        $this->b = $this->seedWorld($this->tenantB, 'b');
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        // El down() de las migraciones no puede restaurar los únicos globales si dos Tenants
        // comparten datos: se limpia todo lo sembrado para que el próximo refresh pueda revertir.
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

    /** Siembra un "mundo" mínimo y completo en el Tenant indicado, con los mismos nombres en cada uno. */
    private function seedWorld(int $tenantId, string $tag): array
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'Medico', 'last_name' => $tag,
            'email' => "medico-{$tag}@test.com", 'password' => 'hash', 'dni' => $tenantId === 1 ? 31000001 : 31000002,
        ]);
        $userId = $this->db->insertID();

        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => 'Pediatria']);
        $specialtyId = $this->db->insertID();

        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => 'Consulta', 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => 100,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'tuition' => 'MAT-IGUAL', 'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('medical_schedules')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'day_of_week' => 1,
            'start_time' => '08:00:00', 'end_time' => '12:00:00',
        ]);
        $scheduleId = $this->db->insertID();

        $this->db->table('medical_service')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'service_id' => $serviceId,
        ]);

        return [
            'user' => $userId, 'specialty' => $specialtyId, 'service' => $serviceId,
            'medical' => $medicalId, 'schedule' => $scheduleId,
        ];
    }

    private function rowExists(string $table, int $id): bool
    {
        return (int) $this->db->table($table)->where('id', $id)->countAllResults() === 1;
    }

    public function testSameSpecialtyNameCanExistInTwoTenantsButNotTwiceInOne(): void
    {
        // El mundo A y el B ya comparten el nombre 'Pediatria' (uno por Tenant).
        $this->assertSame(2, (int) $this->db->table('specialties')->where('name', 'Pediatria')->countAllResults());

        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $this->db->table('specialties')->insert(['tenant_id' => $this->tenantA, 'name' => 'Pediatria']);
    }

    public function testSpecialtyModelIsScopedToTheTenant(): void
    {
        $this->actAs($this->tenantA);
        $model = new SpecialtyModel();

        $this->assertSame($this->a['specialty'], $model->findByName('Pediatria')->getId());
        $this->assertNotNull($model->find($this->a['specialty']));
        $this->assertNull($model->find($this->b['specialty']));

        $filter = new SpecialtyFilterRequest($this->page());
        $this->assertSame(1, $model->count($filter));
        $this->assertCount(1, $model->search($filter));

        $model->delete($this->b['specialty']);
        $this->assertTrue($this->rowExists('specialties', $this->b['specialty']));
    }

    public function testServiceModelIsScopedToTheTenant(): void
    {
        $this->actAs($this->tenantA);
        $model = new ServiceModel();

        $this->assertNotNull($model->find($this->a['service']));
        $this->assertNull($model->find($this->b['service']));
        $this->assertSame($this->a['service'], $model->findByName('Consulta')->getId());
        $this->assertCount(1, $model->findAllActive());

        $filter = new ServiceFilterRequest($this->page());
        $this->assertSame(1, $model->count($filter));
        $this->assertCount(1, $model->search($filter));

        $model->delete($this->b['service']);
        $model->hardDelete($this->b['service']);
        $row = $this->db->table('services')->where('id', $this->b['service'])->get()->getRow();
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->is_active);
    }

    public function testMedicalModelIsScopedToTheTenant(): void
    {
        $this->actAs($this->tenantA);
        $model = new MedicalModel();

        $this->assertNotNull($model->find($this->a['medical']));
        $this->assertNull($model->find($this->b['medical']));
        $this->assertNull($model->findByUserId($this->b['user']));
        $this->assertSame($this->a['medical'], $model->findByTuition('MAT-IGUAL')->getId());
        $this->assertCount(1, $model->findBySpecialtyId($this->a['specialty']));
        $this->assertCount(0, $model->findBySpecialtyId($this->b['specialty']));
        $this->assertSame(0, $model->countBySpecialtyId($this->b['specialty']));
        $this->assertNull($model->findProjection($this->b['medical']));
        $this->assertNull($model->findByUserIdProjection($this->b['user']));

        $filter = new MedicalFilterRequest($this->page());
        $this->assertCount(1, $model->search($filter));
        $this->assertCount(1, $model->searchProjection($filter));
        $this->assertSame(1, $model->countProjection($filter));
        $this->assertCount(1, $model->searchAvailableProjection($filter));
        $this->assertSame(1, $model->countAvailableProjection($filter));

        $model->delete($this->b['medical']);
        $this->assertTrue($this->rowExists('medicals', $this->b['medical']));
    }

    public function testMedicalScheduleModelIsScopedToTheTenant(): void
    {
        $this->actAs($this->tenantA);
        $model = new MedicalScheduleModel();

        $this->assertNotNull($model->find($this->a['schedule']));
        $this->assertNull($model->find($this->b['schedule']));
        $this->assertNull($model->findProjection($this->b['schedule']));
        $this->assertCount(0, $model->findByMedicalId($this->b['medical']));
        $this->assertCount(0, $model->findByMedicalIdProjection($this->b['medical']));
        $this->assertNull($model->findByMedicalAndDay($this->b['medical'], 1));
        $this->assertCount(0, $model->findActiveRangesByMedicalAndDay($this->b['medical'], 1));
        $this->assertCount(0, $model->findActiveRangesByMedicalAndDays($this->b['medical'], [1, 2]));
        $this->assertNull($model->findOverlapping($this->b['medical'], 1, '09:00:00', '10:00:00'));

        // Lo propio sí se ve.
        $this->assertNotNull($model->findOverlapping($this->a['medical'], 1, '09:00:00', '10:00:00'));

        $filter = new MedicalScheduleFilterRequest($this->page());
        $this->assertCount(1, $model->searchProjection($filter));
        $this->assertSame(1, $model->count($filter));

        $model->delete($this->b['schedule']);
        $this->assertTrue($this->rowExists('medical_schedules', $this->b['schedule']));
    }

    public function testMedicalServiceModelIsScopedToTheTenant(): void
    {
        $this->actAs($this->tenantA);
        $model = new MedicalServiceModel();

        $this->assertNotNull($model->findByMedicalAndService($this->a['medical'], $this->a['service']));
        $this->assertNull($model->findByMedicalAndService($this->b['medical'], $this->b['service']));
        $this->assertNotNull($model->findActiveByMedicalAndService($this->a['medical'], $this->a['service']));
        $this->assertNull($model->findActiveByMedicalAndService($this->b['medical'], $this->b['service']));
        $this->assertCount(1, $model->findServicesByMedicalIdProjection($this->a['medical']));
        $this->assertCount(0, $model->findServicesByMedicalIdProjection($this->b['medical']));

        $model->delete($this->b['medical'], $this->b['service']);
        $this->assertSame(1, (int) $this->db->table('medical_service')->where('medical_id', $this->b['medical'])->countAllResults());
    }

    public function testInsertsUseTheContextTenant(): void
    {
        $this->actAs($this->tenantB);

        $specialty = (new SpecialtyModel())->insert(new Specialty(null, 'Cardiologia', null, new DateTime(), new DateTime()));
        $service = (new ServiceModel())->insert(new Service(null, 'Estudio', null, 20, 5, 50.0, true, null, null));
        $medical = (new MedicalModel())->insert(new Medical(null, $this->b['user'], 'MAT-NUEVA', $specialty->getId(), true, null, null));
        $schedule = (new MedicalScheduleModel())->insert(new MedicalSchedule(null, $medical->getId(), 2, '09:00:00', '13:00:00', true, null, null));
        (new MedicalServiceModel())->insertOrUpdate(new MedicalService(null, $medical->getId(), $service->getId(), null, null, true, null, null));

        $expected = [
            'specialties' => $specialty->getId(),
            'services' => $service->getId(),
            'medicals' => $medical->getId(),
            'medical_schedules' => $schedule->getId(),
        ];

        foreach ($expected as $table => $id) {
            $this->assertSame(
                $this->tenantB,
                (int) $this->db->table($table)->where('id', $id)->get()->getRow()->tenant_id,
                "{$table} debe quedar en el Tenant del contexto"
            );
        }

        $this->assertSame(
            $this->tenantB,
            (int) $this->db->table('medical_service')->where('medical_id', $medical->getId())->get()->getRow()->tenant_id
        );

        $this->actAs($this->tenantA);
        $this->assertNull((new SpecialtyModel())->find($specialty->getId()));
        $this->assertNull((new ServiceModel())->find($service->getId()));
        $this->assertNull((new MedicalModel())->find($medical->getId()));
        $this->assertNull((new MedicalScheduleModel())->find($schedule->getId()));
    }

    public function testMigrationDownFailsCleanlyWhenTenantsShareSpecialtyNames(): void
    {
        require_once APPPATH . 'Database/Migrations/2026-10-06-120000_AddTenantIdToCatalogAndMedicalTables.php';

        try {
            (new \App\Database\Migrations\AddTenantIdToCatalogAndMedicalTables())->down();
            $this->fail('Se esperaba RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No se puede revertir', $e->getMessage());
            $this->assertStringContainsString('Pediatria', $e->getMessage());
        }

        foreach (['specialties', 'services', 'medicals', 'medical_schedules', 'medical_service'] as $table) {
            $this->assertNotEmpty(
                $this->db->query("SHOW COLUMNS FROM {$table} WHERE Field = 'tenant_id'")->getResultArray(),
                "{$table} debe conservar tenant_id: el down() no tocó nada"
            );
        }
    }
}
