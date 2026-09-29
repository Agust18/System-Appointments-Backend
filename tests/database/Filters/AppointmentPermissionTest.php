<?php

use App\Constants\Role;
use App\Filters\AppointmentPermission;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AppointmentPermissionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private AppointmentPermission $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new AppointmentPermission();
        session()->destroy();
    }

    private function seedUser(): int
    {
        $this->db->table('users')->insert([
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
        $this->db->table('specialties')->insert(['name' => 'Especialidad ' . uniqid()]);
        $specialtyId = $this->db->insertID();

        $this->db->table('medicals')->insert([
            'user_id'      => $userId,
            'tuition'      => 'MAT-' . uniqid(),
            'specialty_id' => $specialtyId,
        ]);

        return $this->db->insertID();
    }

    private function seedPatient(int $userId): int
    {
        $this->db->table('patients')->insert([
            'user_id'    => $userId,
            'birth_date' => '1990-01-01',
        ]);

        return $this->db->insertID();
    }

    private function seedAppointment(int $medicalId, int $patientId, string $status = 'pending'): int
    {
        $this->db->table('appointments')->insert([
            'medical_id' => $medicalId,
            'patient_id' => $patientId,
            'fecha'      => '2026-10-05',
            'hora'       => '09:00:00',
            'hora_fin'   => '09:30:00',
            'status'     => $status,
        ]);

        return $this->db->insertID();
    }

    private function requestFor(string $path, string $method, ?array $body = null): \CodeIgniter\HTTP\RequestInterface
    {
        $request = Services::request(null, false);
        $request->getUri()->setPath($path);
        $request->setMethod($method);

        if ($body !== null) {
            $request->setBody(json_encode($body));
        }

        return $request;
    }

    public function testAdminCanUpdateAnyAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId);

        session()->set('roleId', Role::ADMIN);
        session()->set('userId', 999);

        $result = $this->filter->before($this->requestFor("appointments/{$appointmentId}", 'put'));

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testPatientCanCancelOwnPendingAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'pending');

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $patientUserId);

        $result = $this->filter->before(
            $this->requestFor("appointments/{$appointmentId}", 'put', ['status' => 'cancelled'])
        );

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testPatientCannotCancelConfirmedAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'confirmed');

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $patientUserId);

        $result = $this->filter->before(
            $this->requestFor("appointments/{$appointmentId}", 'put', ['status' => 'cancelled'])
        );

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testPatientOnOwnPendingAppointmentPassesFilterRegardlessOfRequestedStatus(): void
    {
        // El Filter solo autoriza por ownership + estado "pending" del turno.
        // Que el status pedido sea inválido para un paciente (ej. "confirmed")
        // lo rechaza AppointmentUpdateValidatorService, no este Filter.
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'pending');

        session()->set('roleId', Role::PATIENT);
        session()->set('userId', $patientUserId);

        $result = $this->filter->before(
            $this->requestFor("appointments/{$appointmentId}", 'put', ['status' => 'confirmed'])
        );

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalCanUpdateOwnAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'pending');

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before(
            $this->requestFor("appointments/{$appointmentId}", 'put', ['status' => 'confirmed'])
        );

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
    }

    public function testMedicalCannotUpdateOthersAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'pending');

        $otherMedicalUserId = $this->seedUser();
        $this->seedMedical($otherMedicalUserId);

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $otherMedicalUserId);

        $result = $this->filter->before(
            $this->requestFor("appointments/{$appointmentId}", 'put', ['status' => 'confirmed'])
        );

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testMedicalCannotDeleteAppointment(): void
    {
        $medicalUserId = $this->seedUser();
        $medicalId = $this->seedMedical($medicalUserId);
        $patientUserId = $this->seedUser();
        $patientId = $this->seedPatient($patientUserId);
        $appointmentId = $this->seedAppointment($medicalId, $patientId, 'pending');

        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', $medicalUserId);

        $result = $this->filter->before($this->requestFor("appointments/{$appointmentId}", 'delete'));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
    }

    public function testUnknownAppointmentReturns404(): void
    {
        session()->set('roleId', Role::MEDICAL);
        session()->set('userId', 1);

        $result = $this->filter->before($this->requestFor('appointments/999999', 'put', ['status' => 'confirmed']));

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }
}
