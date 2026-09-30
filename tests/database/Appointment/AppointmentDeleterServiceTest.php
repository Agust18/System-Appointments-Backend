<?php

require_once __DIR__ . '/BookingServiceTestCase.php';

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Exception\Appointment\AppointmentPastCannotBeDeletedException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\AppointmentDeleterService;

/**
 * Regresión: DELETE /appointments/{id} devolvía 500 porque
 * AppointmentDeleterService nunca inicializaba $appointmentModel.
 *
 * @internal
 */
final class AppointmentDeleterServiceTest extends BookingServiceTestCase
{
    private AppointmentDeleterService $service;
    private AppointmentModel $appointmentModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentDeleterService();
        $this->appointmentModel = new AppointmentModel();
    }

    public function testDeletesFutureAppointment(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();

        $created = (new AppointmentBookingService())->book(
            new AppointmentRequest($medicalId, $patientId, $serviceId, '2099-01-05', '09:00', null, 'pending')
        );
        $this->assertNotNull($this->appointmentModel->find($created->getId()));

        $this->service->delete($created->getId());

        $this->assertNull($this->appointmentModel->find($created->getId()));
        $this->assertSame(0, $this->countAppointments());
    }

    public function testDoesNotDeleteOtherAppointments(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $booking = new AppointmentBookingService();

        $toDelete = $booking->book(new AppointmentRequest($medicalId, $patientId, $serviceId, '2099-01-05', '09:00', null));
        $toKeep = $booking->book(new AppointmentRequest($medicalId, $patientId, $serviceId, '2099-01-05', '10:00', null));

        $this->service->delete($toDelete->getId());

        $this->assertNull($this->appointmentModel->find($toDelete->getId()));
        $this->assertNotNull($this->appointmentModel->find($toKeep->getId()));
    }

    public function testPastAppointmentCannotBeDeleted(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 0);
        $patientId = $this->seedPatient();
        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => '2020-01-01',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => 'completed',
        ]);
        $id = (int) $this->db->insertID();

        try {
            $this->service->delete($id);
            $this->fail('Se esperaba AppointmentPastCannotBeDeletedException.');
        } catch (AppointmentPastCannotBeDeletedException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->assertNotNull($this->appointmentModel->find($id));
    }

    public function testNonExistentAppointmentThrowsNotFound(): void
    {
        try {
            $this->service->delete(999999);
            $this->fail('Se esperaba AppointmentNotFoundException.');
        } catch (AppointmentNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }
    }
}
