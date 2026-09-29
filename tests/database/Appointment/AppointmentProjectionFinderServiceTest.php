<?php

use App\Exception\Appointment\AppointmentNotFoundException;
use App\Services\Appointment\Projection\AppointmentProjectionFinderService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Cubre el fix de AppointmentGetController::find(): antes usaba
 * AppointmentFinderService (SQL plano, sin JOIN, perdía serviceId/horaFin y
 * los nombres relacionados). Ahora usa este Service, que expone la misma
 * Projection que ya usa el listado.
 *
 * @internal
 */
final class AppointmentProjectionFinderServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private AppointmentProjectionFinderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentProjectionFinderService();
    }

    private function seedAppointment(): int
    {
        $this->db->table('users')->insert([
            'username' => 'medico_' . uniqid(),
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);
        $medicalUserId = $this->db->insertID();

        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'user_id'      => $medicalUserId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);
        $medicalId = $this->db->insertID();

        $this->db->table('users')->insert([
            'username' => 'paciente_' . uniqid(),
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);
        $patientUserId = $this->db->insertID();

        $this->db->table('patients')->insert([
            'user_id'    => $patientUserId,
            'birth_date' => '1990-01-01',
        ]);
        $patientId = $this->db->insertID();

        $this->db->table('services')->insert([
            'name'              => 'Consulta ' . uniqid(),
            'duration_minutes'  => 30,
            'buffer_minutes'    => 0,
            'slot_step_minutes' => 30,
            'is_active'         => 1,
        ]);
        $serviceId = $this->db->insertID();

        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'fecha'      => '2026-10-10',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'nota'       => 'Control de rutina',
            'status'     => 'confirmed',
        ]);

        return $this->db->insertID();
    }

    public function testFindReturnsProjectionWithRelatedNamesAndServiceId(): void
    {
        $appointmentId = $this->seedAppointment();

        $projection = $this->service->find($appointmentId);

        $this->assertSame($appointmentId, $projection->getId());
        $this->assertNotSame('', $projection->getMedicoNombre());
        $this->assertNotSame('', $projection->getPacienteNombre());
        $this->assertNotSame('', $projection->getServicioNombre());
        $this->assertGreaterThan(0, $projection->getServiceId());
    }

    public function testFindThrowsNotFoundExceptionForMissingId(): void
    {
        $this->expectException(AppointmentNotFoundException::class);
        $this->service->find(999999);
    }

    public function testFindResponseIncludesServiceIdThatWasPreviouslyLost(): void
    {
        $appointmentId = $this->seedAppointment();

        $response = $this->service->findResponse($appointmentId);

        $this->assertSame($appointmentId, $response->id);
        $this->assertGreaterThan(0, $response->serviceId);
        $this->assertNotSame('', $response->medicoNombre);
        $this->assertNotSame('', $response->pacienteNombre);
        $this->assertNotSame('', $response->servicioNombre);
        $this->assertSame('confirmed', $response->status);
    }
}
