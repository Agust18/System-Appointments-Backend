<?php

use App\Constants\Role;
use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\DoctorNotAvailableTimeException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\PublicBookingLimitExceededException;
use App\Exception\Patient\PublicBookingIdentityException;
use App\Services\Appointment\PublicAppointmentCreatorService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * Flujo completo de la reserva pública: validación del turno, resolución de la
 * identidad (casos A–E), tope de turnos y reserva atómica.
 *
 * El envío real de emails queda deshabilitado vaciando EMAIL_HOST: el aviso es
 * best-effort (sendSafely) y solo se loguea el fallo.
 *
 * @internal
 */
final class PublicAppointmentCreatorServiceTest extends BookingServiceTestCase
{
    private const DNI = 30123456;
    private const PHONE = '2325506499';

    /** Un lunes (day_of_week = 1) siempre futuro. */
    private string $monday;

    private PublicAppointmentCreatorService $service;
    private mixed $previousEmailHost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousEmailHost = $_ENV['EMAIL_HOST'] ?? null;
        $_ENV['EMAIL_HOST'] = '';

        $this->monday = (new DateTime('next monday'))->modify('+7 days')->format('Y-m-d');
        $this->service = new PublicAppointmentCreatorService();

        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
        session()->destroy();
    }

    protected function tearDown(): void
    {
        if ($this->previousEmailHost === null) {
            unset($_ENV['EMAIL_HOST']);
        } else {
            $_ENV['EMAIL_HOST'] = $this->previousEmailHost;
        }

        parent::tearDown();
    }

    /**
     * @return array{0:int,1:int} [medicalId, serviceId] con agenda de lunes 09:00-12:00
     */
    private function scenario(): array
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->db->table('medical_schedules')->insert(['tenant_id' => 1,
            'medical_id'  => $medicalId,
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '12:00:00',
            'is_active'   => 1,
        ]);

        return [$medicalId, $serviceId];
    }

    private function request(
        int $medicalId,
        int $serviceId,
        string $hora = '09:00',
        int $dni = self::DNI,
        string $email = 'nuevo@test.com',
        string $phone = self::PHONE,
        ?string $fecha = null
    ): PublicAppointmentRequest {
        return new PublicAppointmentRequest(
            // Nombre único: el down() de ReplaceUsernameWithFirstLastName reconstruye
            // users.username (UNIQUE) desde nombre + apellido al refrescar la base.
            new GuestPatientRequest('Juana_' . uniqid(), 'Pérez', $email, $phone, $dni),
            $medicalId,
            $serviceId,
            $fecha ?? $this->monday,
            $hora,
            'Primera consulta'
        );
    }

    /**
     * @return array{0:int,1:int} [userId, patientId]
     */
    private function seedExistingPatient(int $dni = self::DNI, string $email = 'existente@test.com', ?string $phone = self::PHONE): array
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'existente_' . uniqid(),
            'last_name'  => 'Original',
            'email'      => $email,
            'phone'      => $phone,
            'password'   => null,
            'dni'        => $dni,
            'role_id'    => Role::PATIENT,
        ]);
        $userId = (int) $this->db->insertID();

        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => null]);

        return [$userId, (int) $this->db->insertID()];
    }

    private function seedFutureAppointment(int $medicalId, int $serviceId, int $patientId, int $weeksAhead, string $status = 'confirmed'): void
    {
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => (new DateTime('next monday'))->modify('+' . $weeksAhead . ' weeks')->format('Y-m-d'),
            'hora'       => '10:00:00',
            'hora_fin'   => '10:30:00',
            'status'     => $status,
        ]);
    }

    private function countUsers(): int
    {
        return (int) $this->db->table('users')->countAllResults();
    }

    private function assertIdentityLockReleased(int $dni = self::DNI): void
    {
        $row = $this->db->query('SELECT IS_FREE_LOCK(?) AS is_free', ['public_identity_' . $dni])->getRow();

        $this->assertSame(1, (int) $row->is_free, 'El lock por DNI debe quedar liberado.');
    }

    // ------------------------------------------------------------- caso A

    public function testBookingWithNewIdentityCreatesGuestPatientAndAConfirmedAppointment(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $usersBefore = $this->countUsers();

        $response = $this->service->create($this->request($medicalId, $serviceId, '09:30'));

        $this->assertSame('confirmed', $response->status);
        $this->assertSame($this->monday, $response->fecha);
        $this->assertSame('09:30', $response->hora);
        $this->assertSame($usersBefore + 1, $this->countUsers());
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('users', ['dni' => self::DNI, 'password' => null, 'is_verified' => 0]);
        $this->seeInDatabase('appointments', [
            'id'         => $response->id,
            'medical_id' => $medicalId,
            'service_id' => $serviceId,
            'fecha'      => $this->monday,
            'hora'       => '09:30:00',
            'hora_fin'   => '10:00:00',
            'status'     => 'confirmed',
            'nota'       => 'Primera consulta',
        ]);
        $this->assertLockReleased($medicalId, $this->monday);
        $this->assertIdentityLockReleased();
    }

    public function testWorksWithoutAnAuthenticatedSession(): void
    {
        [$medicalId, $serviceId] = $this->scenario();

        $this->assertNull(session()->get('roleId'));

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame('confirmed', $response->status);
    }

    public function testResponseIsMinimalAndExposesNoPersonalData(): void
    {
        [$medicalId, $serviceId] = $this->scenario();

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame(
            ['id', 'fecha', 'hora', 'medicoNombre', 'servicioNombre', 'status'],
            array_keys(get_object_vars($response))
        );
        $this->assertStringStartsWith('medico_', $response->medicoNombre);
        $this->assertStringStartsWith('Servicio ', $response->servicioNombre);
        $this->assertStringNotContainsString((string) self::DNI, json_encode($response));
        $this->assertStringNotContainsString('nuevo@test.com', json_encode($response));
    }

    // ---------------------------------------------------------- casos B/E

    public function testBookingWithMatchingPhoneReusesTheExistingPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        [, $patientId] = $this->seedExistingPatient();
        $usersBefore = $this->countUsers();

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame($usersBefore, $this->countUsers());
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('appointments', ['id' => $response->id, 'patient_id' => $patientId]);
    }

    // ------------------------------------------------------------ caso B'

    public function testPhoneMismatchIsRejectedAndNothingIsCreated(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $this->seedExistingPatient();
        $usersBefore = $this->countUsers();

        try {
            $this->service->create($this->request($medicalId, $serviceId, '09:00', self::DNI, 'otro@test.com', '1144445555'));
            $this->fail('Se esperaba PublicBookingIdentityException');
        } catch (PublicBookingIdentityException $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->assertSame($usersBefore, $this->countUsers());
        $this->assertSame(0, $this->countAppointments());
    }

    // -------------------------------------------------------------- caso C

    public function testEmailOfAnotherUserIsRejectedAndNothingIsCreated(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $this->seedExistingPatient(30999999, 'ocupado@test.com');
        $usersBefore = $this->countUsers();

        $this->expectException(PublicBookingIdentityException::class);

        try {
            $this->service->create($this->request($medicalId, $serviceId, '09:00', self::DNI, 'ocupado@test.com'));
        } finally {
            $this->assertSame($usersBefore, $this->countUsers());
            $this->assertSame(0, $this->countAppointments());
        }
    }

    // ----------------------------------------------- validación previa a la identidad

    public function testSlotOutsideTheScheduleFailsBeforeCreatingAnyUserOrPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $usersBefore = $this->countUsers();

        try {
            $this->service->create($this->request($medicalId, $serviceId, '11:45'));
            $this->fail('Se esperaba DoctorNotAvailableTimeException');
        } catch (DoctorNotAvailableTimeException) {
            // esperado
        }

        $this->assertSame($usersBefore, $this->countUsers());
        $this->seeNumRecords(0, 'patients', []);
        $this->assertSame(0, $this->countAppointments());
    }

    public function testInactiveMedicalFailsBeforeCreatingAnyUserOrPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $medicalUserId = (int) $this->db->table('medicals')->where('id', $medicalId)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $medicalUserId)->update(['is_active' => 0]);
        $usersBefore = $this->countUsers();

        try {
            $this->service->create($this->request($medicalId, $serviceId));
            $this->fail('Se esperaba MedicalNotAvailableException');
        } catch (MedicalNotAvailableException $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->assertSame($usersBefore, $this->countUsers());
        $this->seeNumRecords(0, 'patients', []);
    }

    // ----------------------------------------------------------- horario ocupado

    public function testTakenSlotReturns409AndLeavesTheHarmlessGuestForTheNextAttempt(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        [, $otherPatientId] = $this->seedExistingPatient(30999999, 'otro@test.com', '1144445555');
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $otherPatientId,
            'service_id' => $serviceId,
            'fecha'      => $this->monday,
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'confirmed',
        ]);
        $usersBefore = $this->countUsers();

        try {
            $this->service->create($this->request($medicalId, $serviceId, '09:00'));
            $this->fail('Se esperaba AppointmentAlreadyExistsException');
        } catch (AppointmentAlreadyExistsException $e) {
            $this->assertSame(409, $e->getCode());
        }

        // El turno no se creó. La identidad sí quedó (se resolvió antes de reservar) y se reutiliza.
        $this->assertSame(1, $this->countAppointments());
        $this->assertSame($usersBefore + 1, $this->countUsers());
        $this->assertLockReleased($medicalId, $this->monday);
        $this->assertIdentityLockReleased();

        $retry = $this->service->create($this->request($medicalId, $serviceId, '10:00'));

        $this->assertSame('confirmed', $retry->status);
        $this->assertSame($usersBefore + 1, $this->countUsers());
    }

    // ----------------------------------------------------------------- tope

    public function testPatientWithThreeActiveFutureAppointmentsCannotBookAnother(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        [, $patientId] = $this->seedExistingPatient();
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 2);
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 3, 'pending');
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 4);

        try {
            $this->service->create($this->request($medicalId, $serviceId));
            $this->fail('Se esperaba PublicBookingLimitExceededException');
        } catch (PublicBookingLimitExceededException $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertStringContainsString('3 turnos', $e->getMessage());
        }

        $this->assertSame(3, $this->countAppointments());
    }

    public function testCancelledCompletedAndPastAppointmentsDoNotCountTowardsTheLimit(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        [, $patientId] = $this->seedExistingPatient();
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 2);
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 3);
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 4, 'cancelled');
        $this->seedFutureAppointment($medicalId, $serviceId, $patientId, 5, 'completed');
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => '2020-01-06',
            'hora'       => '10:00:00',
            'hora_fin'   => '10:30:00',
            'status'     => 'confirmed',
        ]);

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame('confirmed', $response->status);
    }

    public function testTheLimitOnlyCountsTheAppointmentsOfThatPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        [, $otherPatientId] = $this->seedExistingPatient(30999999, 'otro@test.com', '1144445555');
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 2);
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 3);
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 4);

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame('confirmed', $response->status);
    }
}
