<?php

use App\Constants\Role;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;
use App\Services\Permissions\PermissionsService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * @internal
 */
final class PermissionsServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PermissionsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionsService();
    }

    private function seedUser(): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'user_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'    => uniqid() . '@test.com',
            'password' => 'hash',
            'dni'      => random_int(1000000, 9999999),
        ]);

        return $this->db->insertID();
    }

    private function seedMedical(int $userId): int
    {
        $this->db->table('specialties')->insert(['tenant_id' => 1, 'name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert(['tenant_id' => 1,
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function seedPatient(int $userId): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1,
            'first_name' => 'Paciente', 'last_name' => 'Test', 'email' => uniqid() . '@test.com', 'phone' => '1155550000', 'dni' => random_int(10000000, 99999999),
            'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    private function seedAppointment(int $medicalId, int $patientId, string $status = 'pending'): Appointment
    {
        $this->db->table('appointments')->insert(['tenant_id' => 1,
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => '2026-10-05',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => $status,
        ]);

        return (new AppointmentModel())->find($this->db->insertID());
    }

    // canViewPatient

    public function testCanViewPatientAdminAlwaysTrue(): void
    {
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->assertTrue($this->service->canViewPatient(Role::ADMIN, 999, $patientId));
    }

    public function testCanViewPatientReceptionAlwaysTrue(): void
    {
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->assertTrue($this->service->canViewPatient(Role::RECEPTION, 999, $patientId));
    }



    public function testCanViewPatientMedicalWithRelationTrue(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->seedAppointment($medicalId, $patientId);

        $this->assertTrue($this->service->canViewPatient(Role::MEDICAL, $medicalUserId, $patientId));
    }

    public function testCanViewPatientMedicalWithoutRelationFalse(): void
    {
        $medicalUserId = $this->seedUser();
        $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $this->assertFalse($this->service->canViewPatient(Role::MEDICAL, $medicalUserId, $patientId));
    }

    // canViewAppointment



    public function testCanViewAppointmentOwnerMedicalTrue(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId);

        $this->assertTrue($this->service->canViewAppointment(Role::MEDICAL, $medicalUserId, $appointment));
    }

    public function testCanViewAppointmentOtherMedicalFalse(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $otherMedicalUserId = $this->seedUser();
        $this->seedMedical($otherMedicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId);

        $this->assertFalse($this->service->canViewAppointment(Role::MEDICAL, $otherMedicalUserId, $appointment));
    }

    // canEditAppointment

    public function testCanEditAppointmentOwnerMedicalTrue(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId);

        $this->assertTrue($this->service->canEditAppointment(Role::MEDICAL, $medicalUserId, $appointment));
    }

    public function testCanEditAppointmentOtherMedicalFalse(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $otherMedicalUserId = $this->seedUser();
        $this->seedMedical($otherMedicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId);

        $this->assertFalse($this->service->canEditAppointment(Role::MEDICAL, $otherMedicalUserId, $appointment));
    }

    public function testCanEditAppointmentPatientAlwaysFalse(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId);

        $this->assertFalse($this->service->canEditAppointment(Role::PATIENT, $patientUserId, $appointment));
    }

    // canCancelAppointment




    public function testCanCancelAppointmentMedicalAlwaysFalse(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);

        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);

        $appointment = $this->seedAppointment($medicalId, $patientId, 'pending');

        $this->assertFalse($this->service->canCancelAppointment(Role::MEDICAL, $medicalUserId, $appointment));
    }

    // canDeleteAppointment

    public function testCanDeleteAppointmentAdminTrue(): void
    {
        $this->assertTrue($this->service->canDeleteAppointment(Role::ADMIN));
    }

    public function testCanDeleteAppointmentReceptionTrue(): void
    {
        $this->assertTrue($this->service->canDeleteAppointment(Role::RECEPTION));
    }

    public function testCanDeleteAppointmentMedicalFalse(): void
    {
        $this->assertFalse($this->service->canDeleteAppointment(Role::MEDICAL));
    }

    public function testCanDeleteAppointmentPatientFalse(): void
    {
        $this->assertFalse($this->service->canDeleteAppointment(Role::PATIENT));
    }
}
