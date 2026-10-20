<?php

use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\DoctorNotAvailableException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\PublicBookingLimitExceededException;
use App\Services\Appointment\PublicAppointmentCreatorService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * Flujo completo de la reserva pública: validación del turno, ficha del paciente por DNI
 * (se reutiliza sin modificarla), tope de turnos y reserva atómica.
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
            new GuestPatientRequest('Juana', 'Pérez', $email, $phone, $dni),
            $medicalId,
            $serviceId,
            $fecha ?? $this->monday,
            $hora,
            'Primera consulta'
        );
    }

    private function seedExistingPatient(int $dni = self::DNI, string $email = 'existente@test.com', ?string $phone = self::PHONE): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1,
            'first_name' => 'Original',
            'last_name'  => 'Registrado',
            'email'      => $email,
            'phone'      => $phone,
            'dni'        => $dni,
            'birth_date' => null,
        ]);

        return (int) $this->db->insertID();
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

    // --------------------------------------------------------- DNI nuevo

    public function testBookingWithANewDniCreatesThePatientRecordAndAConfirmedAppointment(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $usersBefore = (int) $this->db->table('users')->countAllResults();

        $response = $this->service->create($this->request($medicalId, $serviceId, '09:30'));

        $this->assertSame('confirmed', $response->status);
        $this->assertSame($this->monday, $response->fecha);
        $this->assertSame('09:30', $response->hora);
        $this->assertSame($usersBefore, (int) $this->db->table('users')->countAllResults(), 'no se crea ningún usuario');
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('patients', [
            'dni'        => self::DNI,
            'first_name' => 'Juana',
            'last_name'  => 'Pérez',
            'email'      => 'nuevo@test.com',
            'phone'      => self::PHONE,
        ]);
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
            ['id', 'fecha', 'hora', 'medicoNombre', 'servicioNombre', 'status', 'accessCode', 'accessToken'],
            array_keys(get_object_vars($response))
        );
        $this->assertStringStartsWith('medico_', $response->medicoNombre);
        $this->assertStringStartsWith('Servicio ', $response->servicioNombre);
        $this->assertStringNotContainsString((string) self::DNI, json_encode($response));
        $this->assertStringNotContainsString('nuevo@test.com', json_encode($response));
    }

    // ------------------------------------------------------- DNI existente

    public function testBookingWithAnExistingDniReusesThePatientAndNeverModifiesTheRecord(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $patientId = $this->seedExistingPatient();

        // Nombre, email y teléfono distintos a los guardados: el DNI alcanza y los datos tipeados se ignoran.
        $response = $this->service->create(
            $this->request($medicalId, $serviceId, '09:00', self::DNI, 'otro@test.com', '1144445555')
        );

        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('appointments', ['id' => $response->id, 'patient_id' => $patientId]);
        $this->seeInDatabase('patients', [
            'id'         => $patientId,
            'first_name' => 'Original',
            'last_name'  => 'Registrado',
            'email'      => 'existente@test.com',
            'phone'      => self::PHONE,
        ]);
    }

    public function testTheSameDniInAnotherTenantIsADifferentPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $this->db->table('tenants')->insert(['slug' => 'otro-tenant', 'name' => 'Otro']);
        $otherTenant = (int) $this->db->insertID();
        $this->db->table('patients')->insert([
            'tenant_id' => $otherTenant, 'first_name' => 'Ajena', 'last_name' => 'Otro', 'dni' => self::DNI,
        ]);
        $foreignPatientId = (int) $this->db->insertID();

        try {
            $response = $this->service->create($this->request($medicalId, $serviceId));

            $appointment = $this->db->table('appointments')->where('id', $response->id)->get()->getRow();
            $this->assertNotSame($foreignPatientId, (int) $appointment->patient_id);
            $this->seeNumRecords(2, 'patients', ['dni' => self::DNI]);
        } finally {
            $this->db->table('patients')->where('tenant_id', $otherTenant)->delete();
            $this->db->table('tenants')->where('id', $otherTenant)->delete();
        }
    }

    // ----------------------------------------------- validación previa a la ficha

    public function testSlotOutsideTheScheduleFailsBeforeCreatingAnyPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();

        try {
            $this->service->create($this->request($medicalId, $serviceId, '11:45'));
            $this->fail('Se esperaba DoctorNotAvailableException');
        } catch (DoctorNotAvailableException) {
            // esperado
        }

        $this->seeNumRecords(0, 'patients', []);
        $this->assertSame(0, $this->countAppointments());
    }

    public function testInactiveMedicalFailsBeforeCreatingAnyPatient(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $medicalUserId = (int) $this->db->table('medicals')->where('id', $medicalId)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $medicalUserId)->update(['is_active' => 0]);

        try {
            $this->service->create($this->request($medicalId, $serviceId));
            $this->fail('Se esperaba MedicalNotAvailableException');
        } catch (MedicalNotAvailableException $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(0, 'patients', []);
    }

    // ----------------------------------------------------------- horario ocupado

    public function testTakenSlotReturns409AndTheNextAttemptReusesThePatientRecord(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $otherPatientId = $this->seedExistingPatient(30999999, 'otro@test.com', '1144445555');
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $otherPatientId,
            'service_id' => $serviceId,
            'fecha'      => $this->monday,
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'confirmed',
        ]);

        try {
            $this->service->create($this->request($medicalId, $serviceId, '09:00'));
            $this->fail('Se esperaba AppointmentAlreadyExistsException');
        } catch (AppointmentAlreadyExistsException $e) {
            $this->assertSame(409, $e->getCode());
        }

        // El turno no se creó. La ficha sí quedó (se resolvió antes de reservar) y se reutiliza.
        $this->assertSame(1, $this->countAppointments());
        $this->seeNumRecords(2, 'patients', []);
        $this->assertLockReleased($medicalId, $this->monday);

        $retry = $this->service->create($this->request($medicalId, $serviceId, '10:00'));

        $this->assertSame('confirmed', $retry->status);
        $this->seeNumRecords(2, 'patients', []);
    }

    // ----------------------------------------------------------------- tope

    public function testPatientWithThreeActiveFutureAppointmentsCannotBookAnother(): void
    {
        [$medicalId, $serviceId] = $this->scenario();
        $patientId = $this->seedExistingPatient();
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
        $patientId = $this->seedExistingPatient();
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
        $otherPatientId = $this->seedExistingPatient(30999999, 'otro@test.com', '1144445555');
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 2);
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 3);
        $this->seedFutureAppointment($medicalId, $serviceId, $otherPatientId, 4);

        $response = $this->service->create($this->request($medicalId, $serviceId));

        $this->assertSame('confirmed', $response->status);
    }
}
