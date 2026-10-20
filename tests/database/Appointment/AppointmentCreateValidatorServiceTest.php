<?php

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\AppointmentInPastException;
use App\Exception\Appointment\DoctorNotAvailableException;
use App\Exception\Appointment\InvalidInitialStatusException;
use App\Exception\Appointment\MedicalCannotCreateAppointmentException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Exception\Patient\PatientNotFoundException;
use App\Services\Appointment\Validations\AppointmentCreateValidatorService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * Tests de caracterización de AppointmentCreateValidatorService (el flujo
 * autenticado). Se escribieron antes de extraer AppointmentSlotValidatorService
 * y fijan el comportamiento que esa extracción no puede cambiar.
 *
 * @internal
 */
final class AppointmentCreateValidatorServiceTest extends BookingServiceTestCase
{
    /** Un lunes (day_of_week = 1) siempre futuro, para no depender de la fecha de ejecución. */
    private string $monday;

    private AppointmentCreateValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = (new DateTime('next monday'))->modify('+7 days')->format('Y-m-d');
        $this->validator = new AppointmentCreateValidatorService();

        session()->destroy();
        session()->set('roleId', Role::ADMIN);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        parent::tearDown();
    }

    /**
     * @return array{0:int} [patientId]
     */
    private function seedPatientUser(): array
    {
        $this->db->table('patients')->insert(['tenant_id' => 1, 'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com', 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999), 'birth_date' => '1990-01-01']);

        return [(int) $this->db->insertID()];
    }

    private function seedSchedule(int $medicalId, int $dayOfWeek, string $start, string $end): void
    {
        $this->db->table('medical_schedules')->insert(['tenant_id' => 1,
            'medical_id'  => $medicalId,
            'day_of_week' => $dayOfWeek,
            'start_time'  => $start,
            'end_time'    => $end,
            'is_active'   => 1,
        ]);
    }

    private function request(
        int $medicalId,
        int $patientId,
        int $serviceId,
        string $hora = '09:00',
        string $status = 'confirmed',
        ?string $fecha = null
    ): AppointmentRequest {
        return new AppointmentRequest($medicalId, $patientId, $serviceId, $fecha ?? $this->monday, $hora, null, $status);
    }

    /**
     * Médico con servicio de 30 min y agenda lunes 09:00-12:00, más un paciente válido.
     *
     * @return array{0:int,1:int,2:int} [medicalId, serviceId, patientId]
     */
    private function validScenario(int $duration = 30, int $buffer = 0, ?int $custom = null): array
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService($duration, $buffer, $custom);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');
        [$patientId] = $this->seedPatientUser();

        return [$medicalId, $serviceId, $patientId];
    }

    // ---------------------------------------------------------------- roles

    public function testAdminCanCreateWithAnyStatus(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario();

        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:00', 'confirmed'));
        $this->addToAssertionCount(1);
    }

    public function testMedicalCannotCreateAppointments(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario();
        session()->set('roleId', Role::MEDICAL);

        $this->expectException(MedicalCannotCreateAppointmentException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:00', 'pending'));
    }

    public function testReceptionCanOnlyCreateConfirmedAppointments(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario();
        session()->set('roleId', Role::RECEPTION);

        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:00', 'confirmed'));
        $this->addToAssertionCount(1);

        $this->expectException(InvalidInitialStatusException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:00', 'pending'));
    }




    // --------------------------------------------------------------- médico

    public function testMedicalWithInactiveUserIsRejectedWith409(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario();
        $medicalUserId = (int) $this->db->table('medicals')->where('id', $medicalId)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $medicalUserId)->update(['is_active' => 0]);

        try {
            $this->validator->validate($this->request($medicalId, $patientId, $serviceId));
            $this->fail('Se esperaba MedicalNotAvailableException');
        } catch (MedicalNotAvailableException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }

    // ------------------------------------------------------------- paciente



    public function testUnknownPatientIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService();
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');

        $this->expectException(PatientNotFoundException::class);
        $this->validator->validate($this->request($medicalId, 999999, $serviceId));
    }

    // ---------------------------------------------------------------- fecha

    public function testPastDateIsRejected(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario();

        $this->expectException(AppointmentInPastException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:00', 'confirmed', '2020-01-06'));
    }

    // ------------------------------------------------------------- servicio

    public function testInactiveServiceIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, false);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');
        [$patientId] = $this->seedPatientUser();

        $this->expectException(ServiceNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId));
    }

    public function testServiceNotOfferedByTheMedicalIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, true, false);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');
        [$patientId] = $this->seedPatientUser();

        $this->expectException(MedicalServiceNotOfferedException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId));
    }

    // -------------------------------------------------------------- horario

    public function testDayWithoutSchedulesIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService();
        [$patientId] = $this->seedPatientUser();
        $this->seedSchedule($medicalId, 2, '09:00:00', '12:00:00'); // solo martes

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId));
    }

    public function testBlockEndingExactlyAtTheRangeEndIsAccepted(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario(30);

        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:30'));
        $this->addToAssertionCount(1);
    }

    public function testBlockOverflowingTheRangeEndIsRejected(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario(30);

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:45'));
    }

    public function testStartBeforeTheRangeStartIsRejected(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario(30);

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '08:30'));
    }

    public function testBufferCountsTowardsTheBlock(): void
    {
        [$medicalId, $serviceId, $patientId] = $this->validScenario(30, 15);

        // 11:15 + (30 + 15) = 12:00 → entra justo.
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:15'));
        $this->addToAssertionCount(1);

        // 11:30 + 45 = 12:15 → se pasa por el buffer.
        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:30'));
    }

    public function testMedicalCustomDurationOverridesTheServiceDuration(): void
    {
        // Servicio base de 30 min, pero este médico lo hace en 60.
        [$medicalId, $serviceId, $patientId] = $this->validScenario(30, 0, 60);

        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:00'));
        $this->addToAssertionCount(1);

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '11:30'));
    }

    public function testBlockFittingInASecondRangeIsAccepted(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->seedSchedule($medicalId, 1, '09:00:00', '10:00:00');
        $this->seedSchedule($medicalId, 1, '15:00:00', '18:00:00');
        [$patientId] = $this->seedPatientUser();

        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '17:30'));
        $this->addToAssertionCount(1);
    }

    public function testBlockSpanningTheGapBetweenRangesIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->seedSchedule($medicalId, 1, '09:00:00', '10:00:00');
        $this->seedSchedule($medicalId, 1, '10:15:00', '12:00:00');
        [$patientId] = $this->seedPatientUser();

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $patientId, $serviceId, '09:45'));
    }
}
