<?php

use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotResolvedException;
use App\Models\Metrics\MetricsModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Las métricas de un Tenant no incluyen datos de otro. Cada Tenant tiene volúmenes y precios
 * distintos, de modo que cualquier mezcla cambia los números:
 *
 *   Tenant A: precio base 100, precio personalizado 150 (médico-servicio)
 *             3 turnos: 2 completed, 1 cancelled              -> revenue 300
 *   Tenant B: precio base 40, sin precio personalizado
 *             5 turnos: 3 completed, 1 pending, 1 confirmed vencido (noshow) -> revenue 120
 *
 * Todos en marzo de 2026 (fechas pasadas, resultado determinista).
 *
 * @internal
 */
final class MetricsTenantIsolationTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private const FROM = '2026-03-01';
    private const TO = '2026-03-31';

    private int $tenantA = 1;
    private int $tenantB;

    /** @var array<string, int> */
    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        $this->a = $this->seedWorld($this->tenantA, 'a', 100, 150, [
            ['2026-03-10', '09:00:00', 'completed'],
            ['2026-03-11', '10:00:00', 'completed'],
            ['2026-03-12', '11:00:00', 'cancelled'],
        ]);
        $this->b = $this->seedWorld($this->tenantB, 'b', 40, null, [
            ['2026-03-10', '09:00:00', 'completed'],
            ['2026-03-10', '10:00:00', 'completed'],
            ['2026-03-10', '11:00:00', 'completed'],
            ['2026-03-15', '09:00:00', 'pending'],
            ['2026-03-16', '09:00:00', 'confirmed'],
        ]);

        $this->actAs($this->tenantA);
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        foreach (['appointments', 'medical_service', 'medicals', 'patients', 'services', 'specialties', 'users'] as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    private function seedUser(int $tenantId, string $tag, string $role): int
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => $role, 'last_name' => $tag,
            'email' => "{$role}-{$tag}@test.com", 'password' => 'hash', 'dni' => random_int(10000000, 99999999),
        ]);

        return $this->db->insertID();
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $appointments [fecha, hora, status]
     */
    private function seedWorld(int $tenantId, string $tag, float $price, ?float $customPrice, array $appointments): array
    {
        $medicalUser = $this->seedUser($tenantId, $tag, 'medico');
        $patientUser = $this->seedUser($tenantId, $tag, 'paciente');

        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => "Esp {$tag}"]);
        $specialtyId = $this->db->insertID();

        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => "Srv {$tag}", 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => $price,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'tenant_id' => $tenantId, 'user_id' => $medicalUser, 'tuition' => "MAT-{$tag}", 'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('patients')->insert(['tenant_id' => $tenantId, 'user_id' => $patientUser, 'birth_date' => '1990-01-01']);
        $patientId = $this->db->insertID();

        if ($customPrice !== null) {
            $this->db->table('medical_service')->insert([
                'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'service_id' => $serviceId, 'custom_price' => $customPrice,
            ]);
        }

        foreach ($appointments as [$fecha, $hora, $status]) {
            $this->seedAppointment($tenantId, $medicalId, $patientId, $serviceId, $fecha, $hora, $status);
        }

        return ['medical' => $medicalId, 'patient' => $patientId, 'service' => $serviceId];
    }

    private function seedAppointment(int $tenantId, int $medicalId, int $patientId, int $serviceId, string $fecha, string $hora, string $status): void
    {
        $this->db->table('appointments')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'patient_id' => $patientId, 'service_id' => $serviceId,
            'fecha' => $fecha, 'hora' => $hora, 'hora_fin' => date('H:i:s', strtotime($hora) + 1800), 'status' => $status,
        ]);
    }

    /** @return array<string, float|int> */
    private function kpis(?int $medicalId = null): array
    {
        $row = (new MetricsModel())->getKpis(self::FROM, self::TO, $medicalId);

        return [
            'total' => (int) $row->total, 'pending' => (int) $row->pending, 'confirmed' => (int) $row->confirmed,
            'cancelled' => (int) $row->cancelled, 'completed' => (int) $row->completed,
            'noshow' => (int) $row->noshow, 'revenue' => (float) $row->revenue,
        ];
    }

    public function testKpisOnlyCountTheContextTenant(): void
    {
        $this->actAs($this->tenantA);
        $this->assertSame(
            ['total' => 3, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 1, 'completed' => 2, 'noshow' => 0, 'revenue' => 300.0],
            $this->kpis()
        );

        $this->actAs($this->tenantB);
        $this->assertSame(
            ['total' => 5, 'pending' => 1, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 3, 'noshow' => 1, 'revenue' => 120.0],
            $this->kpis()
        );
    }

    public function testKpisFilteredByAnotherTenantsMedicalReturnNothing(): void
    {
        $kpis = $this->kpis($this->b['medical']);

        $this->assertSame(0, $kpis['total']);
        $this->assertSame(0.0, $kpis['revenue']);
    }

    public function testByMedicalOnlyListsTheContextTenantsMedicals(): void
    {
        $model = new MetricsModel();

        $this->actAs($this->tenantA);
        $rows = $model->getByMedical(self::FROM, self::TO);
        $this->assertCount(1, $rows);
        $this->assertSame($this->a['medical'], (int) $rows[0]->medical_id);
        $this->assertSame([3, 2, 1], [(int) $rows[0]->total, (int) $rows[0]->completed, (int) $rows[0]->cancelled]);

        $this->actAs($this->tenantB);
        $rows = $model->getByMedical(self::FROM, self::TO);
        $this->assertCount(1, $rows);
        $this->assertSame($this->b['medical'], (int) $rows[0]->medical_id);
        $this->assertSame([5, 3, 0], [(int) $rows[0]->total, (int) $rows[0]->completed, (int) $rows[0]->cancelled]);
    }

    public function testMonthlyTrendOnlyCountsTheContextTenant(): void
    {
        $model = new MetricsModel();

        $this->actAs($this->tenantA);
        $rows = $model->getMonthlyTrend(null, self::FROM, self::TO);
        $this->assertCount(1, $rows);
        $this->assertSame('2026-03', $rows[0]->month);
        $this->assertSame([3, 2, 1, 0], [(int) $rows[0]->total, (int) $rows[0]->completed, (int) $rows[0]->cancelled, (int) $rows[0]->noshow]);

        $this->actAs($this->tenantB);
        $rows = $model->getMonthlyTrend(null, self::FROM, self::TO);
        $this->assertSame([5, 3, 0, 1], [(int) $rows[0]->total, (int) $rows[0]->completed, (int) $rows[0]->cancelled, (int) $rows[0]->noshow]);
    }

    public function testTopServicesAndSpecialtiesOnlyShowTheContextTenant(): void
    {
        $model = new MetricsModel();

        $this->actAs($this->tenantA);
        $services = $model->getTopServices(self::FROM, self::TO, null);
        $this->assertCount(1, $services);
        $this->assertSame(['Srv a', 2], [$services[0]->service_name, (int) $services[0]->total]);

        $specialties = $model->getTopSpecialties(self::FROM, self::TO, null);
        $this->assertCount(1, $specialties);
        $this->assertSame(['Esp a', 2], [$specialties[0]->specialty_name, (int) $specialties[0]->total]);

        $this->actAs($this->tenantB);
        $services = $model->getTopServices(self::FROM, self::TO, null);
        $this->assertSame(['Srv b', 5], [$services[0]->service_name, (int) $services[0]->total]);

        $specialties = $model->getTopSpecialties(self::FROM, self::TO, null);
        $this->assertSame(['Esp b', 5], [$specialties[0]->specialty_name, (int) $specialties[0]->total]);
    }

    public function testHeatmapOnlyCountsTheContextTenant(): void
    {
        $model = new MetricsModel();
        $sum = static fn (array $rows): int => array_sum(array_map(static fn ($r) => (int) $r->total, $rows));

        $this->actAs($this->tenantA);
        $this->assertSame(2, $sum($model->getScheduleHeatmap(self::FROM, self::TO, null)));

        $this->actAs($this->tenantB);
        $this->assertSame(5, $sum($model->getScheduleHeatmap(self::FROM, self::TO, null)));
    }

    public function testRecentAppointmentsOnlyShowTheContextTenant(): void
    {
        $model = new MetricsModel();

        $this->actAs($this->tenantA);
        $rows = $model->getRecentAppointments(self::FROM, self::TO, null);
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame('paciente a', $row->patient_name);
            $this->assertSame('medico a', $row->medical_name);
        }

        $this->actAs($this->tenantB);
        $rows = $model->getRecentAppointments(self::FROM, self::TO, null);
        $this->assertCount(5, $rows);
        foreach ($rows as $row) {
            $this->assertSame('paciente b', $row->patient_name);
        }
    }

    public function testRevenueIgnoresAServiceOrCustomPriceOfAnotherTenant(): void
    {
        // Turno completado del Tenant A que apunta a un servicio del Tenant B (precio 40): el JOIN
        // no debe traer ese precio. Tampoco debe usarse el precio personalizado de otro Tenant.
        $this->seedAppointment($this->tenantA, $this->a['medical'], $this->a['patient'], $this->b['service'], '2026-03-20', '12:00:00', 'completed');

        $kpis = $this->kpis();

        $this->assertSame(4, $kpis['total'], 'el turno existe en el Tenant A');
        $this->assertSame(3, $kpis['completed']);
        $this->assertSame(300.0, $kpis['revenue'], 'su ingreso no suma el precio del servicio del otro Tenant');

        $services = (new MetricsModel())->getTopServices(self::FROM, self::TO, null);
        $this->assertSame(['Srv a', 2], [$services[0]->service_name, (int) $services[0]->total], 'tampoco figura en el ranking de servicios');
    }

    public function testMetricsWithoutAResolvedTenantThrowInsteadOfQueryingEverything(): void
    {
        TenantContext::clear();

        $this->expectException(TenantNotResolvedException::class);

        (new MetricsModel())->getKpis(self::FROM, self::TO, null);
    }
}
