<?php

require_once __DIR__ . '/BookingServiceTestCase.php';

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Services\Appointment\AppointmentBookingService;

/**
 * Test de caracterización: fija el comportamiento ACTUAL de
 * AppointmentBookingService::book() antes de extraer la lógica compartida
 * a AppointmentSlotLockService.
 *
 * @internal
 */
final class AppointmentBookingServiceTest extends BookingServiceTestCase
{
    private AppointmentBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentBookingService();
    }

    private function request(int $medicalId, int $patientId, int $serviceId, string $hora, string $status = 'pending'): AppointmentRequest
    {
        return new AppointmentRequest($medicalId, $patientId, $serviceId, self::FECHA, $hora, 'nota de prueba', $status);
    }

    public function testBooksAppointmentWithDurationPlusBuffer(): void
    {
        // 30 min + 10 buffer -> bloque de 40 min
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 10);
        $patientId = $this->seedPatient();

        $appointment = $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00'));

        $this->assertNotNull($appointment->getId());
        $this->assertSame($medicalId, $appointment->getMedicalId());
        $this->assertSame($patientId, $appointment->getPatientId());
        $this->assertSame($serviceId, $appointment->getServiceId());
        $this->assertSame(self::FECHA, $appointment->getFecha()->format('Y-m-d'));
        $this->assertSame('09:00:00', $appointment->getHora()->format('H:i:s'));
        $this->assertSame('09:40:00', $appointment->getHoraFin()->format('H:i:s'));
        $this->assertSame('nota de prueba', $appointment->getNota());
        $this->assertSame('pending', $appointment->getStatus());
        $this->assertSame(1, $this->countAppointments());
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testAcceptsHoraWithSeconds(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();

        $appointment = $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00:00'));

        $this->assertSame('09:00:00', $appointment->getHora()->format('H:i:s'));
        $this->assertSame('09:30:00', $appointment->getHoraFin()->format('H:i:s'));
    }

    public function testUsesMedicalCustomDurationOverride(): void
    {
        // base 30, override 20, buffer 10 -> bloque de 30 min
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 10, 20);
        $patientId = $this->seedPatient();

        $appointment = $this->service->book($this->request($medicalId, $patientId, $serviceId, '10:00'));

        $this->assertSame('10:30:00', $appointment->getHoraFin()->format('H:i:s'));
    }

    public function testInactiveServiceThrowsServiceNotAvailable(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, false);
        $patientId = $this->seedPatient();

        try {
            $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00'));
            $this->fail('Se esperaba ServiceNotAvailableException.');
        } catch (ServiceNotAvailableException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertSame(0, $this->countAppointments());
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testNonExistentServiceThrowsServiceNotAvailable(): void
    {
        $medicalId = $this->seedMedical();
        $patientId = $this->seedPatient();

        $this->expectException(ServiceNotAvailableException::class);
        $this->service->book($this->request($medicalId, $patientId, 999999, '09:00'));
    }

    public function testMedicalNotOfferingServiceThrowsMedicalServiceNotOffered(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0, null, true, false);
        $patientId = $this->seedPatient();

        try {
            $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00'));
            $this->fail('Se esperaba MedicalServiceNotOfferedException.');
        } catch (MedicalServiceNotOfferedException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertSame(0, $this->countAppointments());
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testOverlapThrowsAlreadyExistsAndRollsBack(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        try {
            // 09:15 -> 09:45 pisa 09:00-09:30
            $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:15'));
            $this->fail('Se esperaba AppointmentAlreadyExistsException.');
        } catch (AppointmentAlreadyExistsException $e) {
            $this->assertGreaterThanOrEqual(400, $e->getCode());
            $this->assertLessThan(500, $e->getCode());
        }

        $this->assertSame(1, $this->countAppointments());
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testAdjacentAppointmentsDoNotOverlap(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        $after = $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:30'));
        $before = $this->service->book($this->request($medicalId, $patientId, $serviceId, '08:30'));

        $this->assertSame('09:30:00', $after->getHora()->format('H:i:s'));
        $this->assertSame('09:00:00', $before->getHoraFin()->format('H:i:s'));
        $this->assertSame(3, $this->countAppointments());
    }

    public function testCancelledAppointmentDoesNotBlockSlot(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00', 'cancelled');

        $appointment = $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00'));

        $this->assertSame('09:00:00', $appointment->getHora()->format('H:i:s'));
        $this->assertSame(2, $this->countAppointments());
    }

    public function testOverlapOnlyAppliesToSameMedical(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $otherMedicalId = $this->seedMedical();
        $this->offerService($otherMedicalId, $serviceId);
        $patientId = $this->seedPatient();
        $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        $appointment = $this->service->book($this->request($otherMedicalId, $patientId, $serviceId, '09:00'));

        $this->assertSame($otherMedicalId, $appointment->getMedicalId());
    }

    public function testLockIsReleasedAfterSuccessfulBooking(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();

        $this->service->book($this->request($medicalId, $patientId, $serviceId, '09:00'));

        $this->assertLockReleased($medicalId, self::FECHA);
    }
}
