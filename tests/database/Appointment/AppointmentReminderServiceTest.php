<?php

use App\Constants\TenantStatus;
use App\Dto\Request\Email\EmailRequest;
use App\Entity\Tenant\Tenant;
use App\Services\Appointment\AppointmentReminderService;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * El recordatorio recorre los Tenants activos, fija y limpia el TenantContext por cada uno y
 * solo envía los turnos del Tenant que está procesando.
 *
 * @internal
 */
final class AppointmentReminderServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private string $tomorrow;

    /** @var list<string> destinatarios de los emails que "salieron" */
    private array $sentTo = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomorrow = date('Y-m-d', strtotime('+1 day'));
        $this->sentTo   = [];

        $this->seedTenantWithAppointment(1, 'a');
    }

    protected function tearDown(): void
    {
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));

        foreach (['appointments', 'medicals', 'patients', 'services', 'specialties', 'users'] as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function service(?callable $sender = null): AppointmentReminderService
    {
        return new AppointmentReminderService($sender ?? function (EmailRequest $request): void {
            $this->sentTo[] = $request->getTo();
        });
    }

    private function createTenant(string $slug, string $status = TenantStatus::ACTIVE): int
    {
        $this->db->table('tenants')->insert(['slug' => $slug, 'name' => "Tenant {$slug}", 'status' => $status]);

        return $this->db->insertID();
    }

    /** Siembra un turno confirmado para mañana en el Tenant indicado; su paciente tiene el email "paciente-{tag}@test.com". */
    private function seedTenantWithAppointment(int $tenantId, string $tag): void
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'Medico', 'last_name' => $tag,
            'email' => "medico-{$tag}@test.com", 'password' => 'hash', 'dni' => random_int(10000000, 49999999),
        ]);
        $medicalUser = $this->db->insertID();

        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'Paciente', 'last_name' => $tag,
            'email' => "paciente-{$tag}@test.com", 'password' => 'hash', 'dni' => random_int(50000000, 99999999),
        ]);
        $patientUser = $this->db->insertID();

        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => "Esp {$tag}"]);
        $specialtyId = $this->db->insertID();

        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => "Srv {$tag}", 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => 10,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'tenant_id' => $tenantId, 'user_id' => $medicalUser, 'tuition' => "MAT-{$tag}", 'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('patients')->insert(['tenant_id' => $tenantId, 'user_id' => $patientUser, 'birth_date' => '1990-01-01']);
        $patientId = $this->db->insertID();

        $this->db->table('appointments')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'patient_id' => $patientId, 'service_id' => $serviceId,
            'fecha' => $this->tomorrow, 'hora' => '09:00:00', 'hora_fin' => '09:30:00', 'status' => 'confirmed',
        ]);
    }

    public function testEachActiveTenantOnlyRemindsItsOwnAppointments(): void
    {
        $this->seedTenantWithAppointment($this->createTenant('tenant-b'), 'b');

        $results = $this->service()->sendToAllActiveTenants();

        $this->assertCount(2, $results);
        $this->assertSame(['demo', 'tenant-b'], array_map(static fn ($r) => $r['tenant']->getSlug(), $results));
        $this->assertSame([1, 1], array_column($results, 'sent'));

        sort($this->sentTo);
        $this->assertSame(['paciente-a@test.com', 'paciente-b@test.com'], $this->sentTo);
    }

    public function testSuspendedTenantsAreSkipped(): void
    {
        $this->seedTenantWithAppointment($this->createTenant('suspendido', TenantStatus::SUSPENDED), 's');

        $results = $this->service()->sendToAllActiveTenants();

        $this->assertSame(['demo'], array_map(static fn ($r) => $r['tenant']->getSlug(), $results));
        $this->assertSame(['paciente-a@test.com'], $this->sentTo, 'el Tenant suspendido no recibe recordatorios');
    }

    public function testNoActiveTenantsMeansNothingToDo(): void
    {
        $this->db->query("UPDATE tenants SET status = 'suspended'");

        $this->assertSame([], $this->service()->sendToAllActiveTenants());
        $this->assertSame([], $this->sentTo);
    }

    public function testContextIsRestoredToWhatItWasBefore(): void
    {
        $this->seedTenantWithAppointment($this->createTenant('tenant-b'), 'b');

        $before = new Tenant(1, 'demo', 'Demo', 'active', null, null);
        TenantContext::set($before);
        $this->service()->sendToAllActiveTenants();
        $this->assertSame($before, TenantContext::current(), 'se restaura el contexto previo');

        TenantContext::clear();
        $this->service()->sendToAllActiveTenants();
        $this->assertFalse(TenantContext::isSet(), 'sin contexto previo queda limpio: ningún Tenant se filtra al siguiente proceso');
    }

    public function testAFailedSendIsCountedAndDoesNotStopTheOthers(): void
    {
        $this->seedTenantWithAppointment($this->createTenant('tenant-b'), 'b');

        $failing = function (EmailRequest $request): void {
            if ($request->getTo() === 'paciente-a@test.com') {
                throw new RuntimeException('SMTP caído');
            }
            $this->sentTo[] = $request->getTo();
        };

        $results = $this->service($failing)->sendToAllActiveTenants();

        $this->assertSame([0, 1], array_column($results, 'sent'));
        $this->assertSame([1, 0], array_column($results, 'errors'));
        $this->assertStringContainsString('SMTP caído', $results[0]['failures'][0]);
        $this->assertSame(['paciente-b@test.com'], $this->sentTo, 'el fallo del Tenant A no corta al B');
    }

    public function testSendForCurrentTenantUsesTheContextTenant(): void
    {
        $this->seedTenantWithAppointment($tenantB = $this->createTenant('tenant-b'), 'b');

        TenantContext::set(new Tenant($tenantB, 'tenant-b', 'Tenant tenant-b', 'active', null, null));
        $result = $this->service()->sendForCurrentTenant();

        $this->assertSame(1, $result['found']);
        $this->assertSame(['paciente-b@test.com'], $this->sentTo);
    }
}
