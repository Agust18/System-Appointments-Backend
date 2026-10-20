<?php

use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Exception\Appointment\AppointmentInPastException;
use App\Exception\Appointment\DoctorNotAvailableException;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Services\Appointment\Validations\PublicAppointmentCreateValidatorService;

require_once __DIR__ . '/BookingServiceTestCase.php';

/**
 * El validador de la reserva pública no depende de la sesión: quien reserva no
 * está autenticado.
 *
 * @internal
 */
final class PublicAppointmentCreateValidatorServiceTest extends BookingServiceTestCase
{
    /** Un lunes (day_of_week = 1) siempre futuro. */
    private string $monday;

    private PublicAppointmentCreateValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = (new DateTime('next monday'))->modify('+7 days')->format('Y-m-d');
        $this->validator = new PublicAppointmentCreateValidatorService();

        session()->destroy();
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

    private function request(int $medicalId, int $serviceId, string $hora = '09:00', ?string $fecha = null): PublicAppointmentRequest
    {
        return new PublicAppointmentRequest(
            new GuestPatientRequest('Juana', 'Pérez', 'juana@example.com', '2325506499', 30123456),
            $medicalId,
            $serviceId,
            $fecha ?? $this->monday,
            $hora
        );
    }

    /**
     * @return array{0:int,1:int} [medicalId, serviceId] con agenda de lunes 09:00-12:00
     */
    private function validScenario(): array
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');

        return [$medicalId, $serviceId];
    }

    public function testValidRequestPassesWithoutAnySession(): void
    {
        [$medicalId, $serviceId] = $this->validScenario();

        $this->assertNull(session()->get('roleId'));
        $this->assertNull(session()->get('userId'));

        $this->validator->validate($this->request($medicalId, $serviceId));
        $this->addToAssertionCount(1);
    }

    public function testMedicalWithInactiveUserIsRejectedWith409(): void
    {
        [$medicalId, $serviceId] = $this->validScenario();
        $userId = (int) $this->db->table('medicals')->where('id', $medicalId)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $userId)->update(['is_active' => 0]);

        try {
            $this->validator->validate($this->request($medicalId, $serviceId));
            $this->fail('Se esperaba MedicalNotAvailableException');
        } catch (MedicalNotAvailableException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }

    public function testUnknownMedicalIsRejectedWith404(): void
    {
        [, $serviceId] = $this->validScenario();

        try {
            $this->validator->validate($this->request(999999, $serviceId));
            $this->fail('Se esperaba una excepción 404');
        } catch (Exception $e) {
            $this->assertSame(404, $e->getCode());
        }
    }

    public function testPastDateIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->validScenario();

        $this->expectException(AppointmentInPastException::class);
        $this->validator->validate($this->request($medicalId, $serviceId, '09:00', '2020-01-06'));
    }

    public function testInactiveServiceIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, false);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');

        $this->expectException(ServiceNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $serviceId));
    }

    public function testServiceNotOfferedByTheMedicalIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, true, false);
        $this->seedSchedule($medicalId, 1, '09:00:00', '12:00:00');

        $this->expectException(MedicalServiceNotOfferedException::class);
        $this->validator->validate($this->request($medicalId, $serviceId));
    }

    public function testDayWithoutSchedulesIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->seedSchedule($medicalId, 2, '09:00:00', '12:00:00'); // solo martes

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $serviceId));
    }

    public function testBlockOverflowingTheRangeEndIsRejected(): void
    {
        [$medicalId, $serviceId] = $this->validScenario();

        $this->expectException(DoctorNotAvailableException::class);
        $this->validator->validate($this->request($medicalId, $serviceId, '11:45'));
    }
}
