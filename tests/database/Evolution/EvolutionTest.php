<?php

use App\Constants\Role;
use App\Dto\Request\Evolution\EvolutionRequest;
use App\Dto\Request\Evolution\EvolutionUpdateRequest;
use App\Dto\Request\PaginationRequest;
use App\Entity\Tenant\Tenant;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Exception\Evolution\EvolutionAccessDeniedException;
use App\Exception\Evolution\EvolutionAlreadyExistsException;
use App\Exception\Evolution\EvolutionAlreadySignedException;
use App\Exception\Evolution\EvolutionAppointmentNotEligibleException;
use App\Exception\Evolution\EvolutionInvalidCorrectionException;
use App\Exception\Evolution\EvolutionNotFoundException;
use App\Exception\Patient\PatientNotFoundException;
use App\Models\Evolution\EvolutionModel;
use App\Services\Evolution\EvolutionCreatorService;
use App\Services\Evolution\EvolutionFinderService;
use App\Services\Evolution\EvolutionSignerService;
use App\Services\Evolution\EvolutionsSearcherService;
use App\Services\Evolution\EvolutionUpdaterService;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * Evoluciones de consulta (datos clínicos): aislamiento por Tenant, ownership del médico, permisos por
 * rol, estado del turno, inmutabilidad de lo firmado y registro de accesos.
 *
 * Mundo sembrado: el Tenant A tiene dos médicos (m1, m2), un admin, un usuario de recepción y un paciente
 * con turnos de m1 (confirmed, pending y cancelled) y uno de m2 (confirmed). El Tenant B tiene su propio
 * médico, paciente y turno confirmado.
 *
 * @internal
 */
final class EvolutionTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private int $tenantB;

    /** @var array<string, int> */
    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $this->tenantB = $this->db->insertID();

        $this->a = $this->seedWorldA();
        $this->b = $this->seedWorldB();

        $this->actAs(1);
    }

    protected function tearDown(): void
    {
        $this->actAs(1);

        foreach (['evolution_access_log', 'evolutions', 'appointments', 'medicals', 'patients', 'services', 'specialties', 'users'] as $table) {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            $this->db->query("DELETE FROM {$table}");
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    // Aislamiento por Tenant

    public function testOtherTenantCannotReadAnEvolutionOfMyPatient(): void
    {
        $evolution = $this->createDraft($this->a['appointmentConfirmed']);

        $this->actAs($this->tenantB);

        $this->expectException(EvolutionNotFoundException::class);
        (new EvolutionFinderService())->findResponse($this->a['patient'], $evolution->id, Role::ADMIN, $this->b['admin']);
    }

    public function testOtherTenantCannotListEvolutionsOfMyPatient(): void
    {
        $this->createDraft($this->a['appointmentConfirmed']);

        $this->actAs($this->tenantB);

        $this->expectException(PatientNotFoundException::class);
        (new EvolutionsSearcherService())->search($this->a['patient'], new PaginationRequest(1, 10), Role::ADMIN, $this->b['admin']);
    }

    public function testModelNeverReturnsEvolutionsFromAnotherTenant(): void
    {
        $evolution = $this->createDraft($this->a['appointmentConfirmed']);

        $this->actAs($this->tenantB);
        $model = new EvolutionModel();

        $this->assertNull($model->find($evolution->id));
        $this->assertSame(0, $model->findByPatient($this->a['patient'], null, 1, 50)['total']);
    }

    public function testOtherTenantCannotCreateOnMyAppointment(): void
    {
        $this->actAs($this->tenantB);

        $this->expectException(AppointmentNotFoundException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx'),
            $this->b['patient'],
            Role::MEDICAL,
            $this->b['medicalUser']
        );
    }

    // Ownership

    public function testMedicalCannotCreateOnAnotherMedicalsAppointment(): void
    {
        $this->expectException(EvolutionAccessDeniedException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentOfM2'], 'Dx'),
            $this->a['patient'],
            Role::MEDICAL,
            $this->a['m1User']
        );
    }

    public function testCannotCreateOnAnAppointmentOfAnotherPatient(): void
    {
        $this->expectException(AppointmentNotFoundException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx'),
            $this->a['otherPatient'],
            Role::MEDICAL,
            $this->a['m1User']
        );
    }

    public function testMedicalDoesNotSeeEvolutionsOfAnotherMedical(): void
    {
        $evolutionM1 = $this->createDraft($this->a['appointmentConfirmed']);
        $evolutionM2 = $this->createDraft($this->a['appointmentOfM2'], $this->a['m2User']);

        $searcher = new EvolutionsSearcherService();
        $pagination = new PaginationRequest(1, 10);

        $asM1 = $searcher->search($this->a['patient'], $pagination, Role::MEDICAL, $this->a['m1User']);
        $this->assertSame(1, $asM1['total']);
        $this->assertSame($evolutionM1->id, $asM1['data'][0]->id);

        // el admin sí ve las dos
        $this->assertSame(2, $searcher->search($this->a['patient'], $pagination, Role::ADMIN, $this->a['admin'])['total']);

        $this->expectException(EvolutionNotFoundException::class);
        (new EvolutionFinderService())->findResponse($this->a['patient'], $evolutionM2->id, Role::MEDICAL, $this->a['m1User']);
    }

    public function testMedicalCannotEditOrSignAnotherMedicalsDraft(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        try {
            (new EvolutionUpdaterService())->update(new EvolutionUpdateRequest('Otro'), $this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m2User']);
            $this->fail('Otro médico no debería poder editar el borrador.');
        } catch (EvolutionAccessDeniedException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(EvolutionAccessDeniedException::class);
        (new EvolutionSignerService())->sign($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m2User']);
    }

    // Permisos por rol

    public function testReceptionCannotReadNorWrite(): void
    {
        $this->createDraft($this->a['appointmentConfirmed']);

        try {
            (new EvolutionsSearcherService())->search($this->a['patient'], new PaginationRequest(1, 10), Role::RECEPTION, $this->a['reception']);
            $this->fail('Recepción no debería listar evoluciones.');
        } catch (EvolutionAccessDeniedException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(EvolutionAccessDeniedException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx'),
            $this->a['patient'],
            Role::RECEPTION,
            $this->a['reception']
        );
    }

    public function testAdminReadsButDoesNotWrite(): void
    {
        $this->createDraft($this->a['appointmentConfirmed']);

        $list = (new EvolutionsSearcherService())->search($this->a['patient'], new PaginationRequest(1, 10), Role::ADMIN, $this->a['admin']);
        $this->assertSame(1, $list['total']);

        $this->expectException(EvolutionAccessDeniedException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx'),
            $this->a['patient'],
            Role::ADMIN,
            $this->a['admin']
        );
    }

    // Estado del turno

    public function testPendingAndCancelledAppointmentsDoNotAllowCreating(): void
    {
        foreach (['appointmentPending', 'appointmentCancelled'] as $key) {
            try {
                $this->createDraft($this->a[$key]);
                $this->fail("Un turno {$key} no debería habilitar crear.");
            } catch (EvolutionAppointmentNotEligibleException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testOnlyOneActiveEvolutionPerAppointment(): void
    {
        $this->createDraft($this->a['appointmentConfirmed']);

        $this->expectException(EvolutionAlreadyExistsException::class);
        $this->createDraft($this->a['appointmentConfirmed']);
    }

    // Borrador, firma e inmutabilidad

    public function testDraftCanBeEditedByItsAuthor(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        $updated = (new EvolutionUpdaterService())->update(
            new EvolutionUpdateRequest('Diagnóstico nuevo'),
            $this->a['patient'],
            $draft->id,
            Role::MEDICAL,
            $this->a['m1User']
        );

        $this->assertSame('Diagnóstico nuevo', $updated->diagnosis);
        $this->assertSame('draft', $updated->status);
    }

    public function testSigningClosesTheEvolutionAndCompletesTheAppointment(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        $signed = (new EvolutionSignerService())->sign($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);

        $this->assertSame('signed', $signed->status);
        $this->assertNotNull($signed->signedAt);
        $this->assertSame('completed', $this->appointmentStatus($this->a['appointmentConfirmed']));
    }

    public function testSignedEvolutionCannotBeEditedNorSignedAgain(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);
        (new EvolutionSignerService())->sign($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);

        try {
            (new EvolutionUpdaterService())->update(new EvolutionUpdateRequest('Cambio'), $this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);
            $this->fail('Una evolución firmada no se edita.');
        } catch (EvolutionAlreadySignedException) {
            $this->addToAssertionCount(1);
        }

        // aunque el Service se saltee, el UPDATE del Model solo toca borradores
        $entity = (new EvolutionFinderService())->find($draft->id);
        $entity->modify(new EvolutionUpdateRequest('Cambio forzado'));
        (new EvolutionModel())->updateDraft($entity);
        $this->assertNotSame('Cambio forzado', (new EvolutionModel())->find($draft->id)->getDiagnosis());

        $this->expectException(EvolutionAlreadySignedException::class);
        (new EvolutionSignerService())->sign($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);
    }

    public function testSigningFailsAndRollsBackIfTheAppointmentWasCancelled(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);
        $this->db->query("UPDATE appointments SET status = 'cancelled' WHERE id = ?", [$this->a['appointmentConfirmed']]);

        try {
            (new EvolutionSignerService())->sign($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);
            $this->fail('No se firma sobre un turno cancelado.');
        } catch (EvolutionAppointmentNotEligibleException) {
            $this->addToAssertionCount(1);
        }

        $this->assertNull((new EvolutionModel())->find($draft->id)->getSignedAt());
    }

    public function testSignedEvolutionIsCorrectedWithANewOne(): void
    {
        $original = $this->createDraft($this->a['appointmentConfirmed']);
        (new EvolutionSignerService())->sign($this->a['patient'], $original->id, Role::MEDICAL, $this->a['m1User']);

        $correction = (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx corregido', null, null, null, $original->id),
            $this->a['patient'],
            Role::MEDICAL,
            $this->a['m1User']
        );

        $this->assertSame($original->id, $correction->correctsId);
        $this->assertSame('signed', (new EvolutionFinderService())->findResponse($this->a['patient'], $original->id, Role::MEDICAL, $this->a['m1User'])->status);

        // la original ya fue corregida: no se corrige dos veces
        $this->expectException(EvolutionInvalidCorrectionException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Otra', null, null, null, $original->id),
            $this->a['patient'],
            Role::MEDICAL,
            $this->a['m1User']
        );
    }

    public function testADraftCannotBeCorrected(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        $this->expectException(EvolutionInvalidCorrectionException::class);
        (new EvolutionCreatorService())->create(
            new EvolutionRequest($this->a['appointmentConfirmed'], 'Dx', null, null, null, $draft->id),
            $this->a['patient'],
            Role::MEDICAL,
            $this->a['m1User']
        );
    }

    // Registro de accesos

    public function testEveryReadLeavesAnAccessLogRow(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        (new EvolutionsSearcherService())->search($this->a['patient'], new PaginationRequest(1, 10), Role::ADMIN, $this->a['admin']);
        (new EvolutionFinderService())->findResponse($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m1User']);

        $rows = $this->db->query('SELECT * FROM evolution_access_log ORDER BY id')->getResult();

        $this->assertCount(2, $rows);
        $this->assertSame('list', $rows[0]->action);
        $this->assertNull($rows[0]->evolution_id);
        $this->assertSame((string) $this->a['admin'], (string) $rows[0]->user_id);
        $this->assertSame('view', $rows[1]->action);
        $this->assertSame((string) $draft->id, (string) $rows[1]->evolution_id);
        $this->assertSame('1', (string) $rows[1]->tenant_id);
    }

    public function testADeniedReadLeavesNoAccessLogRow(): void
    {
        $draft = $this->createDraft($this->a['appointmentConfirmed']);

        try {
            (new EvolutionFinderService())->findResponse($this->a['patient'], $draft->id, Role::MEDICAL, $this->a['m2User']);
            $this->fail('Otro médico no debería leerla.');
        } catch (EvolutionNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, (int) $this->db->query('SELECT COUNT(*) AS n FROM evolution_access_log')->getRow()->n);
    }

    // Helpers

    private function actAs(int $tenantId): void
    {
        TenantContext::set(new Tenant($tenantId, 'tenant-' . $tenantId, 'Tenant', 'active', null, null));
    }

    private function createDraft(int $appointmentId, ?int $asUserId = null)
    {
        $asUserId ??= $this->a['m1User'];

        return (new EvolutionCreatorService())->create(
            new EvolutionRequest($appointmentId, 'Diagnóstico', 'Observaciones'),
            $this->patientOf($appointmentId),
            Role::MEDICAL,
            $asUserId
        );
    }

    private function patientOf(int $appointmentId): int
    {
        return (int) $this->db->query('SELECT patient_id FROM appointments WHERE id = ?', [$appointmentId])->getRow()->patient_id;
    }

    private function appointmentStatus(int $appointmentId): string
    {
        return $this->db->query('SELECT status FROM appointments WHERE id = ?', [$appointmentId])->getRow()->status;
    }

    private function seedUser(int $tenantId, string $tag): int
    {
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId, 'first_name' => $tag, 'last_name' => 'Test',
            'email' => "{$tag}-" . uniqid() . '@test.com', 'password' => 'hash', 'dni' => random_int(10000000, 99999999),
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

    private function seedPatient(int $tenantId): int
    {
        $this->db->table('patients')->insert([
            'tenant_id' => $tenantId, 'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com',
            'phone' => '1155550000', 'dni' => random_int(10000000, 99999999), 'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    private function seedAppointment(int $tenantId, int $medicalId, int $patientId, int $serviceId, string $hora, string $status): int
    {
        $this->db->table('appointments')->insert([
            'tenant_id' => $tenantId, 'medical_id' => $medicalId, 'patient_id' => $patientId, 'service_id' => $serviceId,
            'fecha' => date('Y-m-d', strtotime('+1 day')), 'hora' => $hora, 'hora_fin' => date('H:i:s', strtotime($hora) + 1800),
            'status' => $status,
        ]);

        return $this->db->insertID();
    }

    private function seedBase(int $tenantId, string $tag): array
    {
        $this->db->table('specialties')->insert(['tenant_id' => $tenantId, 'name' => "Esp {$tag}"]);
        $specialtyId = $this->db->insertID();

        $this->db->table('services')->insert([
            'tenant_id' => $tenantId, 'name' => "Srv {$tag}", 'duration_minutes' => 30, 'buffer_minutes' => 0, 'price' => 100,
        ]);

        return [$specialtyId, $this->db->insertID()];
    }

    private function seedWorldA(): array
    {
        [$specialtyId, $serviceId] = $this->seedBase(1, 'a');

        $m1User = $this->seedUser(1, 'medico1');
        $m2User = $this->seedUser(1, 'medico2');
        $m1 = $this->seedMedical(1, $m1User, $specialtyId);
        $m2 = $this->seedMedical(1, $m2User, $specialtyId);
        $patient = $this->seedPatient(1);
        $otherPatient = $this->seedPatient(1);

        return [
            'm1User' => $m1User, 'm2User' => $m2User,
            'admin' => $this->seedUser(1, 'admin'), 'reception' => $this->seedUser(1, 'recepcion'),
            'patient' => $patient, 'otherPatient' => $otherPatient,
            'appointmentConfirmed' => $this->seedAppointment(1, $m1, $patient, $serviceId, '09:00:00', 'confirmed'),
            'appointmentPending'   => $this->seedAppointment(1, $m1, $patient, $serviceId, '10:00:00', 'pending'),
            'appointmentCancelled' => $this->seedAppointment(1, $m1, $patient, $serviceId, '11:00:00', 'cancelled'),
            'appointmentOfM2'      => $this->seedAppointment(1, $m2, $patient, $serviceId, '09:00:00', 'confirmed'),
        ];
    }

    private function seedWorldB(): array
    {
        [$specialtyId, $serviceId] = $this->seedBase($this->tenantB, 'b');

        $medicalUser = $this->seedUser($this->tenantB, 'medicoB');
        $medical = $this->seedMedical($this->tenantB, $medicalUser, $specialtyId);
        $patient = $this->seedPatient($this->tenantB);

        return [
            'medicalUser' => $medicalUser, 'admin' => $this->seedUser($this->tenantB, 'adminB'), 'patient' => $patient,
            'appointment' => $this->seedAppointment($this->tenantB, $medical, $patient, $serviceId, '09:00:00', 'confirmed'),
        ];
    }
}
