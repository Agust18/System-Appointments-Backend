<?php

use App\Dto\Request\Appointment\PublicAppointmentAccessRequest;
use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Entity\Tenant\Tenant;
use App\Exception\Appointment\AppointmentNotCancellableException;
use App\Exception\Appointment\AppointmentNotModifiableException;
use App\Exception\Patient\PublicBookingIdentityException;
use App\Services\Appointment\AppointmentAccessCodeRegeneratorService;
use App\Services\Appointment\AppointmentAccessService;
use App\Services\Appointment\PublicAppointmentCancellerService;
use App\Services\Appointment\PublicAppointmentCreatorService;
use App\Services\Appointment\PublicAppointmentLookupService;
use App\Services\Patient\PublicIdentityAttemptLimiter;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * `/consulta`: consultar y cancelar un turno con DNI + código o con el token del link.
 * Cubre lo crítico: el código no se guarda en claro, el error es neutral, el DNI se bloquea tras
 * demasiados fallos, un Tenant no alcanza los turnos de otro y la cancelación no se repite.
 *
 * El envío real de emails queda deshabilitado vaciando EMAIL_HOST.
 *
 * @internal
 */
final class PublicAppointmentAccessTest extends BookingServiceTestCase
{
    private const DNI = 30123456;

    private AppointmentAccessService $access;
    private PublicAppointmentLookupService $lookup;
    private PublicAppointmentCancellerService $canceller;
    private mixed $previousEmailHost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousEmailHost = $_ENV['EMAIL_HOST'] ?? null;
        $_ENV['EMAIL_HOST'] = '';

        $this->resetServices();
        Services::injectMock('cache', new MockCache());

        $this->access = new AppointmentAccessService();
        $this->lookup = new PublicAppointmentLookupService();
        $this->canceller = new PublicAppointmentCancellerService();
    }

    protected function tearDown(): void
    {
        TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
        $this->resetServices();

        if ($this->previousEmailHost === null) {
            unset($_ENV['EMAIL_HOST']);
        } else {
            $_ENV['EMAIL_HOST'] = $this->previousEmailHost;
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function seedPatientWithDni(int $dni = self::DNI, int $tenantId = 1): int
    {
        $this->db->table('patients')->insert([
            'tenant_id'  => $tenantId,
            'first_name' => 'Juana',
            'last_name'  => 'Pérez',
            'email'      => 'juana@test.com',
            'phone'      => '2325506499',
            'dni'        => $dni,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Turno futuro con credenciales ya emitidas.
     *
     * @return array{0:int,1:\App\Entity\Appointment\AppointmentAccess} [appointmentId, access]
     */
    private function futureAppointmentWithAccess(int $dni = self::DNI, string $status = 'confirmed', ?string $fecha = null): array
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $patientId = $this->seedPatientWithDni($dni);

        $this->db->table('appointments')->insert([
            'tenant_id'  => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => $fecha ?? date('Y-m-d', strtotime('+3 days')),
            'hora'       => '10:00:00',
            'hora_fin'   => '10:30:00',
            'status'     => $status,
        ]);
        $appointmentId = (int) $this->db->insertID();

        return [$appointmentId, $this->access->issue($appointmentId)];
    }

    private function byCode(int $dni, string $code): PublicAppointmentAccessRequest
    {
        return new PublicAppointmentAccessRequest($dni, $code, null);
    }

    private function byToken(string $token): PublicAppointmentAccessRequest
    {
        return new PublicAppointmentAccessRequest(null, null, $token);
    }

    private function assertNeutralFailure(callable $action): void
    {
        try {
            $action();
            $this->fail('Se esperaba el error neutral de /consulta.');
        } catch (PublicBookingIdentityException $e) {
            $this->assertSame(404, $e->getCode());
        }
    }

    // ------------------------------------------------- emisión de credenciales

    public function testCodeIsEightUnambiguousCharactersAndOnlyTheHashesAreStored(): void
    {
        [$id, $access] = $this->futureAppointmentWithAccess();

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $access->code);
        $this->assertSame(64, strlen($access->token));

        $row = $this->db->table('appointments')->where('id', $id)->get()->getRow();
        $this->assertSame(hash('sha256', $access->code), $row->access_code_hash);
        $this->assertSame(hash('sha256', $access->token), $row->access_token_hash);
        $this->assertNotSame($access->code, $row->access_code_hash);
    }

    public function testPublicBookingReturnsTheCredentialsAndTheyOpenTheAppointment(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->db->table('medical_schedules')->insert([
            'tenant_id' => 1, 'medical_id' => $medicalId, 'day_of_week' => 1,
            'start_time' => '09:00:00', 'end_time' => '12:00:00', 'is_active' => 1,
        ]);
        $monday = (new DateTime('next monday'))->modify('+7 days')->format('Y-m-d');

        $created = (new PublicAppointmentCreatorService())->create(new PublicAppointmentRequest(
            new GuestPatientRequest('Juana', 'Pérez', 'nuevo@test.com', '2325506499', self::DNI),
            $medicalId,
            $serviceId,
            $monday,
            '09:00',
            null
        ));

        $byCode = $this->lookup->find($this->byCode(self::DNI, $created->accessCode));
        $byToken = $this->lookup->find($this->byToken($created->accessToken));

        $this->assertSame($created->id, $byCode->id);
        $this->assertSame($created->id, $byToken->id);
    }

    // --------------------------------------------------------------- consulta

    public function testLookupByDniAndCodeToleratesLowercaseSpacesAndDashes(): void
    {
        [$id, $access] = $this->futureAppointmentWithAccess();
        $typed = strtolower(substr($access->code, 0, 4) . ' - ' . substr($access->code, 4));

        $found = $this->lookup->find($this->byCode(self::DNI, AppointmentAccessService::normalizeCode($typed)));

        $this->assertSame($id, $found->id);
        $this->assertSame(
            ['id', 'fecha', 'hora', 'medicoNombre', 'servicioNombre', 'status'],
            array_keys(get_object_vars($found))
        );
    }

    public function testWrongCodeUnknownDniAndUnknownTokenAreTheSameNeutralError(): void
    {
        [, $access] = $this->futureAppointmentWithAccess();

        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, 'AAAAAAAA')));
        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(99999999, $access->code)));
        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byToken(str_repeat('a', 64))));
    }

    public function testTheCodeOfAnotherPatientDoesNotOpenThisOne(): void
    {
        [, $accessA] = $this->futureAppointmentWithAccess(30111111);
        $this->futureAppointmentWithAccess(30222222);

        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(30222222, $accessA->code)));
    }

    // ------------------------------------------------------ bloqueo por DNI

    public function testDniGetsBlockedAfterTooManyFailuresEvenWithTheRightCode(): void
    {
        [, $access] = $this->futureAppointmentWithAccess();

        for ($i = 0; $i < PublicIdentityAttemptLimiter::MAX_FAILURES; $i++) {
            $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, 'AAAAAAAA')));
        }

        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, $access->code)));
    }

    public function testASuccessfulLookupClearsTheFailureCount(): void
    {
        [$id, $access] = $this->futureAppointmentWithAccess();

        for ($i = 0; $i < PublicIdentityAttemptLimiter::MAX_FAILURES - 1; $i++) {
            $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, 'AAAAAAAA')));
        }
        $this->assertSame($id, $this->lookup->find($this->byCode(self::DNI, $access->code))->id);

        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, 'AAAAAAAA')));
        $this->assertSame($id, $this->lookup->find($this->byCode(self::DNI, $access->code))->id);
    }

    // ---------------------------------------------------- aislamiento por tenant

    public function testAnotherTenantCannotReachTheAppointmentByCodeOrToken(): void
    {
        [, $access] = $this->futureAppointmentWithAccess();

        $this->db->table('tenants')->insert(['slug' => 'otro-tenant', 'name' => 'Otro']);
        $otherTenant = (int) $this->db->insertID();

        try {
            TenantContext::set(new Tenant($otherTenant, 'otro-tenant', 'Otro', 'active', null, null));

            $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, $access->code)));
            $this->assertNeutralFailure(fn () => $this->lookup->find($this->byToken($access->token)));
            $this->assertNeutralFailure(fn () => $this->canceller->cancel($this->byToken($access->token)));
        } finally {
            TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
            $this->db->table('tenants')->where('id', $otherTenant)->delete();
        }

        $this->seeInDatabase('appointments', ['status' => 'confirmed']);
    }

    public function testTheFailuresOfADniInOneTenantDoNotBlockTheSameDniInAnother(): void
    {
        [$id, $access] = $this->futureAppointmentWithAccess();

        $this->db->table('tenants')->insert(['slug' => 'otro-tenant', 'name' => 'Otro']);
        $otherTenant = (int) $this->db->insertID();

        try {
            TenantContext::set(new Tenant($otherTenant, 'otro-tenant', 'Otro', 'active', null, null));
            for ($i = 0; $i < PublicIdentityAttemptLimiter::MAX_FAILURES; $i++) {
                $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, 'AAAAAAAA')));
            }
        } finally {
            TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
            $this->db->table('tenants')->where('id', $otherTenant)->delete();
        }

        $this->assertSame($id, $this->lookup->find($this->byCode(self::DNI, $access->code))->id);
    }

    // ------------------------------------------------------------ cancelación

    public function testConfirmedAndPendingFutureAppointmentsCanBeCancelled(): void
    {
        foreach (['confirmed', 'pending'] as $i => $status) {
            [$id, $access] = $this->futureAppointmentWithAccess(30000000 + $i, $status);

            $response = $this->canceller->cancel($this->byCode(30000000 + $i, $access->code));

            $this->assertSame('cancelled', $response->status);
            $this->seeInDatabase('appointments', ['id' => $id, 'status' => 'cancelled']);
        }
    }

    public function testCancellingByTokenWorks(): void
    {
        [$id, $access] = $this->futureAppointmentWithAccess();

        $this->canceller->cancel($this->byToken($access->token));

        $this->seeInDatabase('appointments', ['id' => $id, 'status' => 'cancelled']);
    }

    public function testAnAlreadyCancelledAppointmentCannotBeCancelledAgain(): void
    {
        [, $access] = $this->futureAppointmentWithAccess();
        $this->canceller->cancel($this->byToken($access->token));

        $this->expectException(AppointmentNotCancellableException::class);
        $this->canceller->cancel($this->byToken($access->token));
    }

    public function testPastAndCompletedAppointmentsCannotBeCancelled(): void
    {
        [$pastId, $pastAccess] = $this->futureAppointmentWithAccess(30111111, 'confirmed', date('Y-m-d', strtotime('-2 days')));
        [$doneId, $doneAccess] = $this->futureAppointmentWithAccess(30222222, 'completed');

        foreach ([[$pastId, $pastAccess, 'confirmed'], [$doneId, $doneAccess, 'completed']] as [$id, $access, $status]) {
            try {
                $this->canceller->cancel($this->byToken($access->token));
                $this->fail('Se esperaba AppointmentNotCancellableException.');
            } catch (AppointmentNotCancellableException $e) {
                $this->assertSame(409, $e->getCode());
            }
            $this->seeInDatabase('appointments', ['id' => $id, 'status' => $status]);
        }
    }

    public function testCancellingWithWrongCredentialsLeavesTheAppointmentUntouched(): void
    {
        [$id] = $this->futureAppointmentWithAccess();

        $this->assertNeutralFailure(fn () => $this->canceller->cancel($this->byCode(self::DNI, 'AAAAAAAA')));

        $this->seeInDatabase('appointments', ['id' => $id, 'status' => 'confirmed']);
    }

    // ------------------------------------------------------------ regeneración

    public function testRegeneratingTheCodeInvalidatesTheOldCodeAndToken(): void
    {
        [$id, $old] = $this->futureAppointmentWithAccess();

        $new = (new AppointmentAccessCodeRegeneratorService())->regenerate($id);

        $this->assertNotSame($old->code, $new->accessCode);
        $this->assertSame($id, $this->lookup->find($this->byCode(self::DNI, $new->accessCode))->id);
        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byToken($old->token)));
        $this->assertNeutralFailure(fn () => $this->lookup->find($this->byCode(self::DNI, $old->code)));
    }

    public function testCodeOfACancelledOrCompletedAppointmentCannotBeRegenerated(): void
    {
        [$id] = $this->futureAppointmentWithAccess(self::DNI, 'completed');

        $this->expectException(AppointmentNotModifiableException::class);
        (new AppointmentAccessCodeRegeneratorService())->regenerate($id);
    }
}
