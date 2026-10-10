<?php

require_once __DIR__ . '/BookingServiceTestCase.php';

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Exception\Appointment\AppointmentAlreadyExistsException;
use App\Exception\Appointment\AppointmentServiceRequiredException;
use App\Services\Appointment\Validations\AppointmentUpdateValidatorService;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\AppointmentBookingService;

/**
 * Test de caracterización: fija el comportamiento ACTUAL de
 * AppointmentBookingService::reschedule()
 * (edición de horario bajo lock + transacción).
 *
 * @internal
 */
final class AppointmentRescheduleTest extends BookingServiceTestCase
{
    private AppointmentBookingService $service;
    private AppointmentModel $appointmentModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentBookingService();
        $this->appointmentModel = new AppointmentModel();
    }

    private function updateRequest(
        ?int $medicalId = null,
        ?int $serviceId = null,
        ?string $fecha = null,
        ?string $hora = null,
        ?string $nota = null,
        ?string $status = null
    ): AppointmentUpdateRequest {
        return new AppointmentUpdateRequest($medicalId, null, $serviceId, $fecha, $hora, $nota, $status);
    }

    private function doUpdate(int $id, AppointmentUpdateRequest $request)
    {
        $appointment = $this->appointmentModel->find($id);

        return $this->service->reschedule($appointment, $request, $id);
    }

    public function testUpdatesHoraAndRecalculatesHoraFin(): void
    {
        // 30 min + 10 buffer -> bloque de 40 min
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 10);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:40:00');

        $updated = $this->doUpdate($id, $this->updateRequest(null, null, null, '11:00', 'nuevo'));

        $this->assertSame($id, $updated->getId());
        $this->assertSame('11:00:00', $updated->getHora()->format('H:i:s'));
        $this->assertSame('11:40:00', $updated->getHoraFin()->format('H:i:s'));
        $this->assertSame('nuevo', $updated->getNota());
        $this->assertSame(1, $this->countAppointments());
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testEmptyRequestKeepsValuesAndRecalculatesHoraFin(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        // hora_fin persistida desactualizada a propósito (09:15) -> se recalcula a 09:30
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:15:00');

        $updated = $this->doUpdate($id, $this->updateRequest());

        $this->assertSame('09:00:00', $updated->getHora()->format('H:i:s'));
        $this->assertSame('09:30:00', $updated->getHoraFin()->format('H:i:s'));
        $this->assertSame('confirmed', $updated->getStatus());
    }

    public function testUsesMedicalCustomDurationOverride(): void
    {
        // base 30, override 20, buffer 10 -> bloque de 30 min
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 10, 20);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        $updated = $this->doUpdate($id, $this->updateRequest(null, null, null, '10:00'));

        $this->assertSame('10:30:00', $updated->getHoraFin()->format('H:i:s'));
    }

    public function testRescheduleOverOwnSlotIsNotAnOverlap(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        // 09:15 -> 09:45 pisa su propio 09:00-09:30: debe permitirse
        $updated = $this->doUpdate($id, $this->updateRequest(null, null, null, '09:15'));

        $this->assertSame('09:15:00', $updated->getHora()->format('H:i:s'));
        $this->assertSame('09:45:00', $updated->getHoraFin()->format('H:i:s'));
    }

    public function testOverlapWithAnotherAppointmentThrowsAndKeepsOriginal(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');
        $this->seedAppointment($medicalId, $patientId, $serviceId, '10:00:00', '10:30:00');

        try {
            $this->doUpdate($id, $this->updateRequest(null, null, null, '10:15'));
            $this->fail('Se esperaba AppointmentAlreadyExistsException.');
        } catch (AppointmentAlreadyExistsException $e) {
            $this->assertGreaterThanOrEqual(400, $e->getCode());
            $this->assertLessThan(500, $e->getCode());
        }

        $persisted = $this->appointmentModel->find($id);
        $this->assertSame('09:00:00', $persisted->getHora()->format('H:i:s'));
        $this->assertSame('09:30:00', $persisted->getHoraFin()->format('H:i:s'));
        $this->assertLockReleased($medicalId, self::FECHA);
    }

    public function testCancelledAppointmentDoesNotBlockUpdate(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');
        $this->seedAppointment($medicalId, $patientId, $serviceId, '10:00:00', '10:30:00', 'cancelled');

        $updated = $this->doUpdate($id, $this->updateRequest(null, null, null, '10:00'));

        $this->assertSame('10:00:00', $updated->getHora()->format('H:i:s'));
    }

    public function testChangingMedicalUsesFinalValuesAndChecksNewMedicalAgenda(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $otherMedicalId = $this->seedMedical();
        $this->offerService($otherMedicalId, $serviceId);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');
        $this->seedAppointment($otherMedicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        try {
            $this->doUpdate($id, $this->updateRequest($otherMedicalId));
            $this->fail('Se esperaba AppointmentAlreadyExistsException por agenda del nuevo médico.');
        } catch (AppointmentAlreadyExistsException) {
            // esperado
        }

        // liberado el horario del nuevo médico, el cambio es válido
        $updated = $this->doUpdate($id, $this->updateRequest($otherMedicalId, null, null, '10:00'));

        $this->assertSame($otherMedicalId, $updated->getMedicalId());
        $this->assertSame('10:30:00', $updated->getHoraFin()->format('H:i:s'));
        $this->assertLockReleased($otherMedicalId, self::FECHA);
    }

    public function testChangingFechaMovesLockAndAppointment(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        $updated = $this->doUpdate($id, $this->updateRequest(null, null, '2026-10-06'));

        $this->assertSame('2026-10-06', $updated->getFecha()->format('Y-m-d'));
        $this->assertLockReleased($medicalId, '2026-10-06');
    }

    public function testChangingToInactiveServiceThrowsServiceNotAvailable(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $inactiveServiceId = $this->seedService(30, 0, false);
        $this->offerService($medicalId, $inactiveServiceId);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        $this->expectException(ServiceNotAvailableException::class);
        $this->doUpdate($id, $this->updateRequest(null, $inactiveServiceId));
    }

    public function testChangingToServiceNotOfferedThrowsMedicalServiceNotOffered(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $notOfferedServiceId = $this->seedService(30, 0, true);
        $patientId = $this->seedPatient();
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:00:00', '09:30:00');

        try {
            $this->doUpdate($id, $this->updateRequest(null, $notOfferedServiceId));
            $this->fail('Se esperaba MedicalServiceNotOfferedException.');
        } catch (MedicalServiceNotOfferedException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertLockReleased($medicalId, self::FECHA);
    }

    private function seedLegacyAppointmentWithoutService(int $medicalId, int $patientId): int
    {
        // service_id es nullable en la tabla (turno legacy). PrimitiveToAppointmentConverter
        // lo castea a 0 al hidratar la entidad.
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => null,
            'fecha'      => self::FECHA,
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'confirmed',
        ]);

        return (int) $this->db->insertID();
    }

    public function testLegacyAppointmentWithoutServiceThrowsServiceRequired(): void
    {
        [$medicalId, ] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedLegacyAppointmentWithoutService($medicalId, $patientId);

        $appointment = $this->appointmentModel->find($id);

        // la regla vive en el validator, que corre antes de tomar el lock
        try {
            (new AppointmentUpdateValidatorService())->validate($this->updateRequest(null, null, null, '10:00'), $appointment, $id);
            $this->fail('Se esperaba AppointmentServiceRequiredException.');
        } catch (AppointmentServiceRequiredException $e) {
            $this->assertSame(422, $e->getCode());
        }

        // Ya no se verifica la liberación del lock (assertLockReleased): esta regla vive en
        // AppointmentUpdateValidatorService, que corre ANTES de tomar el lock, así que nunca se adquiere.
        // Si en el futuro la validación se mueve después del lock, este test debe volver a
        // verificar que el lock quede liberado.
        $persisted = $this->appointmentModel->find($id);
        $this->assertSame('09:00:00', $persisted->getHora()->format('H:i:s'));
    }

    public function testLegacyAppointmentWithoutServiceCanBeEditedWhenServiceIsProvided(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $id = $this->seedLegacyAppointmentWithoutService($medicalId, $patientId);

        $appointment = $this->appointmentModel->find($id);
        $updated = $this->service->reschedule($appointment, $this->updateRequest(null, $serviceId), $id);

        $this->assertSame($serviceId, $updated->getServiceId());
        $this->assertSame('09:30:00', $updated->getHoraFin()->format('H:i:s'));
    }
}
