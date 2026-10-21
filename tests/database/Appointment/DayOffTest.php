<?php

require_once __DIR__ . '/BookingServiceTestCase.php';

use App\Dto\Request\Appointment\AppointmentAvailabilityBatchRequest;
use App\Dto\Request\Appointment\AppointmentAvailabilityRequest;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Request\DayOff\DayOffFilterRequest;
use App\Dto\Request\DayOff\DayOffRequest;
use App\Dto\Request\PaginationRequest;
use App\Exception\DayOff\DayOffException;
use App\Exception\DayOff\DayOffOverlapException;
use App\Services\Appointment\AppointmentAvailabilityBatchService;
use App\Services\Appointment\AppointmentAvailabilityService;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\DayOff\DayOffCreatorService;
use App\Services\DayOff\DaysOffSearcherService;

/**
 * Días no laborables: el bloqueo se respeta en el booking y en la disponibilidad, no toca los
 * turnos ya agendados y no cruza Tenants.
 *
 * @internal
 */
final class DayOffTest extends BookingServiceTestCase
{
    private const ADMIN_ROLE = 1;
    private const LUNES = '2026-11-02';
    private const OTRO_LUNES = '2026-11-09';

    protected function setUp(): void
    {
        parent::setUp();
        session()->set(['userId' => 1, 'roleId' => self::ADMIN_ROLE]);
    }

    protected function tearDown(): void
    {
        $this->db->query('DELETE FROM medical_days_off');
        $this->db->query('DELETE FROM tenants WHERE id <> 1');

        parent::tearDown();
    }

    private function createDayOff(?int $medicalId, string $from, ?string $to = null, ?string $reason = null)
    {
        return (new DayOffCreatorService())->create(new DayOffRequest($medicalId, $from, $to ?? $from, $reason));
    }

    private function seedMondaySchedule(int $medicalId): void
    {
        $this->db->table('medical_schedules')->insert([
            'tenant_id'   => 1,
            'medical_id'  => $medicalId,
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '11:00:00',
            'is_active'   => 1,
        ]);
    }

    public function testBookingOnDayOffFailsForDoctorAndClinicButNotForOtherDoctor(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        [$otherMedicalId] = $this->seedMedicalWithService(30, 0);
        $this->offerService($otherMedicalId, $serviceId);
        $patientId = $this->seedPatient();
        $booking = new AppointmentBookingService();

        // bloqueo de un médico: él no puede reservar, el otro sí
        $this->createDayOff($medicalId, self::FECHA);

        try {
            $booking->book(new AppointmentRequest($medicalId, $patientId, $serviceId, self::FECHA, '10:00', null));
            $this->fail('Se esperaba DayOffException.');
        } catch (DayOffException $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertSame('DAY_OFF', $e->errorCode());
        }
        $this->assertLockReleased($medicalId, self::FECHA);

        $booked = $booking->book(new AppointmentRequest($otherMedicalId, $patientId, $serviceId, self::FECHA, '10:00', null));
        $this->assertNotNull($booked->getId());

        // bloqueo de la clínica: ahora tampoco el otro médico
        $this->createDayOff(null, self::LUNES);
        $this->expectException(DayOffException::class);
        $booking->book(new AppointmentRequest($otherMedicalId, $patientId, $serviceId, self::LUNES, '10:00', null));
    }

    public function testAvailabilityHidesDaysOffForOneDayAndForRange(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $this->seedMondaySchedule($medicalId);
        $this->createDayOff($medicalId, self::LUNES);

        $day = (new AppointmentAvailabilityService())
            ->getAvailableSlots(new AppointmentAvailabilityRequest($medicalId, $serviceId, self::LUNES));
        $this->assertSame([], $day);

        $range = (new AppointmentAvailabilityBatchService())
            ->getAvailableSlotsBatch(new AppointmentAvailabilityBatchRequest($medicalId, $serviceId, self::LUNES, self::OTRO_LUNES));
        $this->assertSame([self::OTRO_LUNES], array_keys($range->data));
    }

    public function testCreatingDayOffReturnsAffectedAppointmentsWithoutTouchingThem(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $affected = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00', 'confirmed');
        $cancelled = $this->seedAppointment($medicalId, $patientId, $serviceId, '10:00:00', '10:30:00', 'cancelled');

        $response = $this->createDayOff($medicalId, '2026-10-01', '2026-10-31', 'Vacaciones');

        $this->assertSame([$affected], array_map(static fn ($a) => $a->id, $response->affectedAppointments));
        $this->assertNotContains($cancelled, array_map(static fn ($a) => $a->id, $response->affectedAppointments));
        $this->assertSame('confirmed', $this->db->table('appointments')->where('id', $affected)->get()->getRow()->status);

        // el mismo alcance no puede solaparse
        $this->expectException(DayOffOverlapException::class);
        $this->createDayOff($medicalId, '2026-10-31', '2026-11-05');
    }

    public function testDayOffOfAnotherTenantDoesNotBlockNorAppear(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $tenantB = $this->db->insertID();
        $this->db->table('medical_days_off')->insert([
            'tenant_id'  => $tenantB,
            'medical_id' => null,
            'date_from'  => self::FECHA,
            'date_to'    => self::FECHA,
            'created_by' => 1,
        ]);

        $booked = (new AppointmentBookingService())
            ->book(new AppointmentRequest($medicalId, $patientId, $serviceId, self::FECHA, '10:00', null));
        $this->assertNotNull($booked->getId());

        $result = (new DaysOffSearcherService())
            ->search(new DayOffFilterRequest(new PaginationRequest(1, 50)), self::ADMIN_ROLE, 1);
        $this->assertSame(0, $result['total']);
    }
}
