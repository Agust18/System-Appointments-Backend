<?php

use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Entity\Appointment\Appointment;
use App\Entity\Tenant\Tenant;
use App\Exception\Tenant\TenantNotResolvedException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Patient\PatientModel;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Un Tenant nunca ve, busca, modifica ni borra los turnos de otro, y el Model nunca escribe con
 * el default: usa el contexto o lanza.
 *
 * Mundo sembrado: el Tenant A tiene 1 turno (pending) y el B tiene 2 (confirmed y cancelled),
 * todos para mañana, con datos paralelos (médico, paciente, especialidad, servicio).
 *
 * @internal
 */
final class AppointmentTenantIsolationTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantA = 1;
    private int $tenantB;

    private string $tomorrow;

    /** @var array<string, int> */
    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomorrow = date('Y-m-d', strtotime('+1 day'));

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        $this->a = $this->seedWorld($this->tenantA, 'a', [['09:00:00', '09:30:00', 'pending']]);
        $this->b = $this->seedWorld($this->tenantB, 'b', [['10:00:00', '10:30:00', 'confirmed'], ['11:00:00', '11:30:00', 'cancelled']]);

        $this->actAs($this->tenantA);
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        foreach (['appointments', 'medical_service', 'medical_schedules', 'medicals', 'patients', 'services', 'specialties', 'users'] as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    private function filter(): AppointmentFilterRequest
    {
        return new AppointmentFilterRequest(new PaginationRequest(1, 50));
    }

    private function seedUser(int $tenantId, string $tag, string $role): int
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => $role, 'last_name' => $tag,
            'email' => "{$role}-{$tag}-" . uniqid() . '@test.com', 'password' => 'hash', 'dni' => random_int(10000000, 99999999),
        ]);

        return $this->db->insertID();
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $appointments [hora, hora_fin, status]
     */
    private function seedWorld(int $tenantId, string $tag, array $appointments): array
    {
        $medicalUser = $this->seedUser($tenantId, $tag, 'medico');
        $patientUser = $this->seedUser($tenantId, $tag, 'paciente');

        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => "Esp {$tag}"]);
        $specialtyId = $this->db->insertID();

        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => "Srv {$tag}", 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => 100,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'tenant_id' => $tenantId, 'user_id' => $medicalUser, 'tuition' => "MAT-{$tag}", 'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('patients')->insert(['tenant_id' => $tenantId, 'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com', 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999), 'birth_date' => '1990-01-01']);
        $patientId = $this->db->insertID();

        $ids = [];
        foreach ($appointments as [$hora, $horaFin, $status]) {
            $ids[] = $this->seedAppointment($tenantId, $medicalId, $patientId, $serviceId, $hora, $horaFin, $status);
        }

        return [
            'medicalUser' => $medicalUser, 'patientUser' => $patientUser, 'service' => $serviceId,
            'medical' => $medicalId, 'patient' => $patientId, 'appointment' => $ids[0], 'appointments' => $ids,
        ];
    }

    private function seedAppointment(int $tenantId, int $medicalId, int $patientId, int $serviceId, string $hora, string $horaFin, string $status): int
    {
        $this->db->table('appointments')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'patient_id' => $patientId, 'service_id' => $serviceId,
            'fecha' => $this->tomorrow, 'hora' => $hora, 'hora_fin' => $horaFin, 'status' => $status,
        ]);

        return $this->db->insertID();
    }

    private function appointmentRow(int $id): ?object
    {
        return $this->db->table('appointments')->where('id', $id)->get()->getRow();
    }

    // --- Listas: lo de un Tenant no aparece en las listas del otro -------------------------------

    public function testListsOnlyShowTheContextTenantsAppointments(): void
    {
        $model = new AppointmentModel();

        $this->actAs($this->tenantA);
        $this->assertSame(1, $model->countProjection($this->filter()));
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->searchProjection($this->filter())));

        $this->actAs($this->tenantB);
        $this->assertSame(2, $model->countProjection($this->filter()));
        $ids = array_map(static fn ($p) => $p->getId(), $model->searchProjection($this->filter()));
        sort($ids);
        $this->assertSame($this->b['appointments'], $ids);
    }

    public function testStatsByStatusOnlyCountsTheContextTenant(): void
    {
        $model = new AppointmentModel();

        $this->actAs($this->tenantA);
        $this->assertSame(['pending' => 1, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0], $model->statsByStatus($this->filter()));

        $this->actAs($this->tenantB);
        $this->assertSame(['pending' => 0, 'confirmed' => 1, 'cancelled' => 1, 'completed' => 0], $model->statsByStatus($this->filter()));
    }

    // --- Búsquedas puntuales ------------------------------------------------------------------

    public function testLookupsCannotReachAnotherTenantsAppointments(): void
    {
        $model = new AppointmentModel();
        $foreign = $this->b['appointment'];

        $this->assertNotNull($model->find($this->a['appointment']));
        $this->assertNull($model->find($foreign));
        $this->assertNotNull($model->findProjection($this->a['appointment']));
        $this->assertNull($model->findProjection($foreign));

        $this->assertCount(0, $model->findByMedicalId($this->b['medical']));
        $this->assertCount(0, $model->findByPatientId($this->b['patient']));
        $this->assertCount(0, $model->findByMedicalUserId($this->b['medicalUser']));
        $this->assertCount(1, $model->findByMedicalId($this->a['medical']), 'control: lo propio sí se ve');

        $this->assertFalse($model->hasRelationWithPatient($this->b['medicalUser'], $this->b['patient']));
        $this->assertTrue($model->hasRelationWithPatient($this->a['medicalUser'], $this->a['patient']), 'control: lo propio sí se ve');
    }

    // --- Solapamiento y disponibilidad ---------------------------------------------------------

    public function testOverlapAndAvailabilityIgnoreAnotherTenantsAppointments(): void
    {
        $model = new AppointmentModel();

        // El turno de las 10:00 es del Tenant B: desde A no existe.
        $this->assertFalse($model->hasOverlap($this->b['medical'], $this->tomorrow, '10:00:00', '10:30:00'));
        $this->assertSame(0, $model->countActiveFutureByPatient($this->b['patient'], new DateTime('today')));
        $this->assertCount(0, $model->findActiveHoursByMedicalAndFecha($this->b['medical'], $this->tomorrow));
        $this->assertCount(0, $model->findActiveHoursByMedicalAndFechaRange($this->b['medical'], $this->tomorrow, $this->tomorrow));

        // Control: lo propio sí cuenta.
        $this->assertTrue($model->hasOverlap($this->a['medical'], $this->tomorrow, '09:00:00', '09:30:00'));
        $this->assertSame(1, $model->countActiveFutureByPatient($this->a['patient'], new DateTime('today')));
        $this->assertCount(1, $model->findActiveHoursByMedicalAndFecha($this->a['medical'], $this->tomorrow));

        // Y desde el Tenant B el mismo horario sí está ocupado.
        $this->actAs($this->tenantB);
        $this->assertTrue($model->hasOverlap($this->b['medical'], $this->tomorrow, '10:00:00', '10:30:00'));
    }

    public function testUpcomingRemindersOnlyIncludeTheContextTenant(): void
    {
        $model = new AppointmentModel();

        $this->actAs($this->tenantA);
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->findUpcomingIn24Hours()));

        // En B solo el confirmado: el cancelado no recibe recordatorio.
        $this->actAs($this->tenantB);
        $this->assertSame([$this->b['appointments'][0]], array_map(static fn ($p) => $p->getId(), $model->findUpcomingIn24Hours()));
    }

    // --- Escrituras ajenas ---------------------------------------------------------------------

    public function testUpdateAndDeleteCannotTouchAnotherTenantsAppointment(): void
    {
        $foreignId = $this->b['appointment'];

        $this->actAs($this->tenantB);
        $entity = (new AppointmentModel())->find($foreignId);
        $this->assertNotNull($entity);

        $this->actAs($this->tenantA);
        $model = new AppointmentModel();

        // El UPDATE no afecta la fila ajena; el Model devuelve el find() posterior, que es null
        // desde A (no cumple el tipo de retorno y lanza TypeError: los Services ya cortaron antes
        // con un 404 al no encontrar el turno).
        try {
            $model->update($entity);
        } catch (\TypeError) {
        }
        $model->delete($foreignId);

        $row = $this->appointmentRow($foreignId);
        $this->assertNotNull($row, 'el DELETE desde otro Tenant no borra la fila');
        $this->assertSame('confirmed', $row->status);
    }

    // --- INSERT: usa el contexto o lanza, nunca el default --------------------------------------

    public function testInsertWritesTheContextTenantNotTheDefault(): void
    {
        $this->actAs($this->tenantB);

        $created = (new AppointmentModel())->insert($this->newAppointment($this->b));

        $this->assertSame($this->tenantB, (int) $this->appointmentRow($created->getId())->tenant_id);
        $this->assertNotSame(1, (int) $this->appointmentRow($created->getId())->tenant_id, 'no debe caer al DEFAULT 1');

        $this->actAs($this->tenantA);
        $this->assertNull((new AppointmentModel())->find($created->getId()));
    }

    public function testInsertWithoutAResolvedTenantThrowsInsteadOfUsingTheDefault(): void
    {
        TenantContext::clear();
        $before = (int) $this->db->table('appointments')->countAllResults();

        try {
            (new AppointmentModel())->insert($this->newAppointment($this->a));
            $this->fail('Se esperaba TenantNotResolvedException');
        } catch (TenantNotResolvedException) {
            $this->assertSame($before, (int) $this->db->table('appointments')->countAllResults());
        }
    }

    private function newAppointment(array $world): Appointment
    {
        return new Appointment(
            null, $world['medical'], $world['patient'], $world['service'],
            new DateTime($this->tomorrow), new DateTime('15:00:00'), new DateTime('15:30:00'),
            null, 'pending', new DateTime(), new DateTime()
        );
    }

    // --- Integridad de JOINs: referencias cruzadas entre Tenants --------------------------------

    public function testProjectionsExcludeAppointmentsThatPointToAnotherTenantsRows(): void
    {
        // Turno del Tenant A que apunta a un paciente o a un médico del Tenant B.
        $toForeignPatient = $this->seedAppointment($this->tenantA, $this->a['medical'], $this->b['patient'], $this->a['service'], '12:00:00', '12:30:00', 'confirmed');
        $toForeignMedical = $this->seedAppointment($this->tenantA, $this->b['medical'], $this->a['patient'], $this->a['service'], '13:00:00', '13:30:00', 'confirmed');

        $model = new AppointmentModel();

        foreach ([$toForeignPatient, $toForeignMedical] as $crossed) {
            $this->assertNotNull($model->find($crossed), 'la fila existe en el Tenant A');
            $this->assertNull($model->findProjection($crossed), 'pero su proyección no debe filtrar datos del otro Tenant');
        }

        $this->assertSame(1, $model->countProjection($this->filter()));
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->searchProjection($this->filter())));
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->findUpcomingIn24Hours()));
        $this->assertSame(
            ['pending' => 1, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0],
            $model->statsByStatus($this->filter()),
            'las estadísticas por estado tampoco cuentan los turnos con referencias cruzadas'
        );
    }

    /**
     * Solo se verifica que no se filtre nada del otro Tenant en las proyecciones y listas. NO se
     * afirma nada sobre countProjection/statsByStatus: no hacen JOIN a `services` (searchProjection
     * sí), así que cuentan este turno aunque no figure en la lista. Esa fila es del propio Tenant A
     * (no es una fuga) y es una inconsistencia previa al multitenant: ver
     * bitacora/pendiente/count-y-stats-de-turnos-sin-join-a-services.md.
     */
    public function testAppointmentWithAForeignServiceDoesNotLeakInProjections(): void
    {
        $toForeignService = $this->seedAppointment($this->tenantA, $this->a['medical'], $this->a['patient'], $this->b['service'], '14:00:00', '14:30:00', 'confirmed');

        $model = new AppointmentModel();

        $this->assertNotNull($model->find($toForeignService), 'la fila existe en el Tenant A');
        $this->assertNull($model->findProjection($toForeignService), 'su proyección no debe mostrar el servicio del otro Tenant');
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->searchProjection($this->filter())));
        $this->assertSame([$this->a['appointment']], array_map(static fn ($p) => $p->getId(), $model->findUpcomingIn24Hours()));
    }

    public function testPatientSearchByMedicalIgnoresAppointmentsOfAnotherTenant(): void
    {
        // Paciente del Tenant A sin turnos propios, y un turno del Tenant B que lo apunta usando
        // el médico del Tenant A: sin `A.tenant_id = P.tenant_id` el paciente aparecería como
        // "atendido por ese médico".
        $this->db->table('users')->insert([
            'tenant_id' => $this->tenantA, 'first_name' => 'Sin', 'last_name' => 'Turnos',
            'email' => 'sin-turnos@test.com', 'password' => 'hash', 'dni' => 39999999,
        ]);
        $this->db->table('patients')->insert(['tenant_id' => $this->tenantA, 'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com', 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999), 'birth_date' => '1990-01-01']);
        $orphanPatient = $this->db->insertID();

        $this->seedAppointment($this->tenantB, $this->a['medical'], $orphanPatient, $this->b['service'], '16:00:00', '16:30:00', 'confirmed');

        $filter = new PatientFilterRequest(new PaginationRequest(1, 50), null, $this->a['medicalUser']);
        $model = new PatientModel();

        $this->assertSame(1, $model->countProjection($filter), 'solo el paciente con un turno propio de ese médico');
        $this->assertSame([$this->a['patient']], array_map(static fn ($p) => $p->getId(), $model->searchProjection($filter)));
    }
}
